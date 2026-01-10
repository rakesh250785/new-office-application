<?php

return [

    'show_warnings' => false,

    'public_path' => null,

    'convert_entities' => true,

    'options' => [

        /* =====================================================
           FONT DIRECTORIES (MUST BE WRITABLE)
        ====================================================== */
        'font_dir' => storage_path('fonts'),
        'font_cache' => storage_path('fonts'),
        'enable_php' => true,

        'temp_dir' => sys_get_temp_dir(),

        'chroot' => realpath(base_path()),

        'allowed_protocols' => [
            'data://' => ['rules' => []],
            'file://' => ['rules' => []],
            'http://' => ['rules' => []],
            'https://' => ['rules' => []],
        ],

        'artifactPathValidation' => null,

        'log_output_file' => null,

        /* =====================================================
           FONT SETTINGS (CRITICAL FIXES)
        ====================================================== */

        // MUST MATCH font_family KEY (case-sensitive)
        'default_font' => 'calibri',

        // Enable subsetting if you want smaller PDFs
        'enable_font_subsetting' => true,

        // REGISTER CALIBRI HERE (MANDATORY)
        'font_family' => [
            'calibri' => [
                'normal' => 'calibri.ttf',
                'bold' => 'calibrib.ttf',
                'italic' => 'calibrii.ttf',
                'bold_italic' => 'calibriz.ttf',
            ],
        ],

        /* =====================================================
           PDF ENGINE
        ====================================================== */
        'pdf_backend' => 'CPDF',

        'default_media_type' => 'screen',
        'default_paper_size' => 'a4',
        'default_paper_orientation' => 'portrait',

        /* =====================================================
           PHP + REMOTE (MUST MATCH Pdf::setOptions)
        ====================================================== */

        // Remote images / fonts if needed
        'enable_remote' => true,

        'allowed_remote_hosts' => null,

        /* =====================================================
           RENDERING
        ====================================================== */
        'dpi' => 96,

        'font_height_ratio' => 1.1,

        'enable_html5_parser' => true,
    ],
];
