<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Printer Method
    |--------------------------------------------------------------------------
    |
    | This option controls which printer integration method to use.
    |
    | Supported: "disabled", "web_bluetooth", "qz_tray", "printnode"
    |
    */

    'method' => env('PRINTER_METHOD', 'disabled'),

    /*
    |--------------------------------------------------------------------------
    | QZ Tray Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for QZ Tray desktop application integration.
    | QZ Tray must be installed and running on the client machine.
    |
    */

    'qz_tray' => [
        'host' => env('QZ_TRAY_HOST', 'localhost'),
        'port' => env('QZ_TRAY_PORT', 8181),
        'secure' => env('QZ_TRAY_SECURE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | PrintNode Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for PrintNode cloud printing service.
    | Requires PrintNode account and API key.
    |
    */

    'printnode' => [
        'api_key' => env('PRINTNODE_API_KEY'),
        'printer_id' => env('PRINTNODE_PRINTER_ID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Web Bluetooth Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Web Bluetooth API (browser-based).
    | Requires HTTPS and compatible browser (Chrome/Edge).
    |
    */

    'web_bluetooth' => [
        'enabled' => env('WEB_BLUETOOTH_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Receipt Settings
    |--------------------------------------------------------------------------
    |
    | General settings for receipt printing.
    |
    */

    'receipt' => [
        'width' => env('RECEIPT_WIDTH', 32), // Characters per line
        'auto_print' => env('AUTO_PRINT_RECEIPTS', true),
        'copies' => env('RECEIPT_COPIES', 1),
    ],

];
