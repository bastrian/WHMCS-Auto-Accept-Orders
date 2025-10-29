<?php
/**
 * Jetserver Auto Accept Orders (WHMCS 8.13.1 Native)
 * Updated by: Bastrian
 *
 * Automatically accepts and provisions paid orders.
 * Includes admin dashboard and activity log viewer.
 */

use WHMCS\Database\Capsule;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

/**
 * Module Configuration
 */
function jetserver_autoacceptorders_config()
{
    return [
        'name'        => 'Jetserver Auto Accept Orders',
        'description' => 'Automatically accepts and provisions paid orders when invoices are paid.',
        'version'     => '3.1.0',
        'author'      => 'Jetserver (Updated by Bastrian)',
        'language'    => 'english',
        'category'    => 'Automation',

        'fields' => [
            'autosetup' => [
                'FriendlyName' => 'Auto Setup Products',
                'Type' => 'yesno',
                'Description' => 'Automatically provision products upon payment.',
                'Default' => 'on',
            ],
            'sendregistrar' => [
                'FriendlyName' => 'Send to Registrar',
                'Type' => 'yesno',
                'Description' => 'Automatically send domains to registrar.',
                'Default' => 'on',
            ],
            'sendemail' => [
                'FriendlyName' => 'Send Emails',
                'Type' => 'yesno',
                'Description' => 'Send welcome and registration confirmation emails.',
                'Default' => 'on',
            ],
            'ispaid' => [
                'FriendlyName' => 'Only Process Paid Invoices',
                'Type' => 'yesno',
                'Description' => 'Only accept orders for fully paid invoices.',
                'Default' => 'on',
            ],
            'paymentmethod' => [
                'FriendlyName' => 'Payment Method Filter',
                'Type' => 'text',
                'Size' => '50',
                'Description' => 'Comma-separated list (e.g. paypal,stripe). Leave empty to allow all methods.',
            ],
            'debugmode' => [
                'FriendlyName' => 'Debug Mode',
                'Type' => 'yesno',
                'Description' => 'Enable extra log output for troubleshooting.',
                'Default' => 'off',
            ],
        ],
    ];
}

/**
 * Activation: create log table
 */
function jetserver_autoacceptorders_activate()
{
    try {
        if (!Capsule::schema()->hasTable('jetserver_autoaccept_log')) {
            Capsule::schema()->create('jetserver_autoaccept_log', function ($table) {
                $table->increments('id');
                $table->integer('orderid')->nullable();
                $table->integer('invoiceid')->nullable();
                $table->string('status', 50)->nullable();
                $table->text('message')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }
        return ['status' => 'success', 'description' => 'Module activated and log table created.'];
    } catch (Exception $e) {
        return ['status' => 'error', 'description' => 'Activation failed: ' . $e->getMessage()];
    }
}

/**
 * Deactivation
 */
function jetserver_autoacceptorders_deactivate()
{
    return ['status' => 'success', 'description' => 'Jetserver Auto Accept Orders deactivated.'];
}

/**
 * Admin Output Page
 */
function jetserver_autoacceptorders_output($vars)
{
    $modulelink = $vars['modulelink'];

    // Clear log if requested
    if (isset($_POST['clearlog'])) {
        Capsule::table('jetserver_autoaccept_log')->truncate();
        echo '<div class="alert alert-success">Log cleared successfully.</div>';
    }

    echo '<h2>Auto Accept Orders Log</h2>';
    echo '<form method="post" style="margin-bottom:10px;">
            <button class="btn btn-danger" name="clearlog" value="1" onclick="return confirm(\'Clear all log entries?\')">Clear Log</button>
          </form>';

    $logs = Capsule::table('jetserver_autoaccept_log')->orderBy('id', 'desc')->limit(50)->get();

    if ($logs->isEmpty()) {
        echo '<p>No log entries found.</p>';
    } else {
        echo '<table class="table table-striped" width="100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Invoice</th>
                        <th>Order</th>
                        <th>Status</th>
                        <th>Message</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>';
        foreach ($logs as $log) {
            echo '<tr>
                    <td>' . $log->id . '</td>
                    <td>#' . $log->invoiceid . '</td>
                    <td>#' . $log->orderid . '</td>
                    <td>' . htmlspecialchars($log->status) . '</td>
                    <td>' . htmlspecialchars($log->message) . '</td>
                    <td>' . $log->created_at . '</td>
                  </tr>';
        }
        echo '</tbody></table>';
    }

    echo '<hr><p style="font-size:12px;color:gray;">Jetserver Auto Accept Orders — Version 3.1.0</p>';
}

/**
 * Hook Logic (Invoice Paid)
 */
add_hook('InvoicePaid', 1, function ($vars) {
    $cfg = Capsule::table('tbladdonmodules')
        ->where('module', 'jetserver_autoacceptorders')
        ->pluck('value', 'setting');

    $autosetup     = ($cfg['autosetup'] ?? 'off') === 'on';
    $sendregistrar = ($cfg['sendregistrar'] ?? 'off') === 'on';
    $sendemail     = ($cfg['sendemail'] ?? 'off') === 'on';
    $ispaid        = ($cfg['ispaid'] ?? 'off') === 'on';
    $debugmode     = ($cfg['debugmode'] ?? 'off') === 'on';
    $paymentfilter = array_filter(array_map('trim', explode(',', $cfg['paymentmethod'] ?? '')));

    $invoiceId = $vars['invoiceid'] ?? null;
    if (!$invoiceId) {
        return;
    }

    $invoice = localAPI('GetInvoice', ['invoiceid' => $invoiceId]);
    if ($invoice['result'] !== 'success') {
        jetserver_log($invoiceId, null, 'error', 'Failed to retrieve invoice: ' . $invoice['message']);
        return;
    }

    $isPaidInvoice = ($invoice['balance'] <= 0);
    $paymentMethod = $invoice['paymentmethod'] ?? '';

    if (!empty($paymentfilter) && !in_array($paymentMethod, $paymentfilter)) {
        jetserver_log($invoiceId, null, 'skipped', "Payment method {$paymentMethod} not allowed");
        return;
    }

    if ($ispaid && !$isPaidInvoice) {
        jetserver_log($invoiceId, null, 'skipped', "Invoice not fully paid");
        return;
    }

    $orders = Capsule::table('tblorders')->where('invoiceid', $invoiceId)->get();
    foreach ($orders as $order) {
        $orderId = $order->id;
        $params = [
            'orderid'       => $orderId,
            'autosetup'     => $autosetup ? 'true' : 'false',
            'sendemail'     => $sendemail ? 'true' : 'false',
            'sendregistrar' => $sendregistrar ? 'true' : 'false',
        ];

        $result = localAPI('AcceptOrder', $params);
        $status = $result['result'] === 'success' ? 'success' : 'error';
        $msg    = $result['message'] ?? 'OK';

        jetserver_log($invoiceId, $orderId, $status, $msg);

        if ($debugmode) {
            logActivity("AutoAcceptOrders [debug]: Order #{$orderId}, Invoice #{$invoiceId}, Status={$status}, Msg={$msg}");
        }
    }
});

/**
 * Helper: Write to custom log table
 */
function jetserver_log($invoiceId, $orderId, $status, $message)
{
    try {
        Capsule::table('jetserver_autoaccept_log')->insert([
            'invoiceid' => $invoiceId,
            'orderid'   => $orderId,
            'status'    => $status,
            'message'   => $message,
            'created_at'=> date('Y-m-d H:i:s'),
        ]);
    } catch (Exception $e) {
        logActivity('AutoAcceptOrders Log Error: ' . $e->getMessage());
    }
}
