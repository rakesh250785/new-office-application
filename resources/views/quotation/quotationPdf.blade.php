<!doctype html>
<html>

<head>
    <meta charset="utf-8" />
    <title>Quotation</title>
    <style>
        body,
        table,
        td,
        th {
            font-family: "Inter", "DejaVu Sans", Arial, Helvetica, sans-serif;
            color: #222;
            font-size: 10px;
            line-height: 1.45;
            letter-spacing: 0.1px;
        }

        /* ---------- GLOBAL / PRINT HINTS ---------- */
        * {
            box-sizing: border-box;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }


        html,
        body {
            height: 100%;
            font-family: "Inter", "DejaVu Sans", Arial, Helvetica, sans-serif;
            margin: 0;
            padding: 6px;
            background: #ffffff;
            color: #222;
            font-size: 10px;
        }

        .paper {
            background: #fff;
            border-radius: 4px;
            padding: 8px;
            margin: 0 auto;
            max-width: 100%;
            box-sizing: border-box;
        }

        @page {
            margin: 6mm;
        }

        /* header / tables */
        .hdr {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            background-color: #fdf1cb;
        }

        .logo-col {
            width: 18%;
            padding-right: 10px;
        }

        .center-col {
            width: 82%;
            vertical-align: middle;
        }

        .logo {
            width: 100%;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            overflow: hidden;
            border-radius: 6px;
        }

        .logo img {
            width: 100%;
            height: auto;
            max-height: 65px;
            object-fit: contain;
            display: block;
        }

        .logo-fallback {
            width: 100%;
            height: 70px;
            background: #0a2a30;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        .company-meta .meta-row,
        .quotation-meta .meta-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            line-height: 1.3;
        }

        .hdr td {
            vertical-align: middle;
            padding: 0 0 4px 6px;
        }

        .center-col {
            padding: 0 8px;
            vertical-align: middle;
        }

        .right-col {
            width: 220px;
            vertical-align: top;
        }

        /* --- Header / logo improvements --- */
        .logo-col {
            width: 140px;
            padding-right: 12px;
        }

        .logo {
            width: 140px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            overflow: hidden;
            background: transparent;
            border-radius: 4px;
        }

        .logo img {
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
            display: block;
        }

        .logo-fallback {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #0a2a30;
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.4px;
        }

        .company-name {
            font-weight: 700;
            font-size: 14px;
            line-height: 1.05;
            color: #111;
            text-align: center;
        }

        .company-meta {
            color: #555;
            font-size: 9.2px;
            line-height: 1.15;
            max-width: 100%;
        }

        .company-meta .meta-row {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
            font-size: 9.2px;
            color: #555;
        }

        .company-meta .muted {
            color: #666;
            font-weight: 600;
            font-size: 9px;
        }

        .title {
            color: #e76b00;
            font-size: 16px;
            font-weight: 700;
            margin: 0 0 4px 0;
        }

        .info-card {
            border-radius: 6px;
            border: 1px solid #f0d9cd;
            background: #fff8f3;
            padding: 8px;
            font-size: 9.5px;
            color: #b85c12;
            min-width: 160px;
            box-sizing: border-box;
        }

        .info-card .row {
            display: flex;
            justify-content: space-between;
            gap: 6px;
            padding: 2px 0;
            color: #333;
            font-size: 9.5px;
        }

        .authorised {
            margin-top: 5px;
            border: 1px solid #eef2f4;
            background: #fff;
            padding: 8px 10px;
            border-radius: 4px;
            font-size: 9px;
            color: #333;
            text-align: center;
            line-height: 1.2;
            word-break: break-word;
            display: block;
        }

        .ship-wrap {
            border-radius: 4px;
            border: 1px solid #e2efe6;
            background: #f7fff8;
        }

        .ship-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5px;
            table-layout: fixed;
        }

        .break-all {
            word-break: break-all;
            overflow-wrap: anywhere;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 12px;
        }

        th,
        td {
            padding: 6px 8px;
            text-align: left;
            vertical-align: top;
            word-break: break-word;
        }

        th {
            background: #f2f2f2;
            font-weight: bold;
            color: #111;
            text-transform: uppercase;
        }

        tr:nth-child(even) {
            background: #fff2e6;
        }

        tr:hover {
            background: #f5f5f5;
        }

        .table-wrapper {
            border: 1.5px solid #fddcb4;
            border-radius: 4px;
            overflow: hidden;
        }

        /* items styling */
        .items tbody tr:nth-child(odd) {
            background-color: #ffffff;
        }

        .items tbody tr:nth-child(even) {
            background-color: #f7fbff;
        }

        .items tbody tr:nth-child(even)+tr .desc {
            background-color: #fdf1cb;
        }

        .items tbody tr:nth-child(odd)+tr .desc {
            background-color: #fdf5e2;
        }

        .items tbody tr:hover {
            background-color: #e8f3ff;
        }

        .items thead th {
            background: #fddcb4;
            color: black;
            padding: 6px;
            font-weight: 700;
            font-size: 9px;
            border: 0.5px solid #fddcb4;
            text-align: left;
            line-height: 1.1;
            vertical-align: middle;
        }

        .items tbody td {
            padding: 5px 6px;
            border: 0.5px solid #fddcb4;
            vertical-align: top;
            font-size: 10px;
            color: #222;
            font-family: "Inter", "DejaVu Sans", Arial, Helvetica, sans-serif;
        }

        .items .desc {
            color: #666;
            font-size: 9.5px;
            padding-left: 8px;
        }

        .items td,
        .ship-table td {
            word-wrap: break-word;
            overflow-wrap: anywhere;
            white-space: normal;
        }

        .items col.serial-col {
            width: 20px !important;
            min-width: 20px !important;
            max-width: 20px !important;
        }

        .items thead th.serial,
        .items tbody td.serial {
            width: 20px !important;
            min-width: 20px !important;
            max-width: 20px !important;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            /* padding: 2px 4px !important; */
            font-weight: 700;
            /* border: 1px solid #e2efe6 !important; */
        }

        .items thead th.hsn,
        .items tbody td.hsn {
            width: 70px;
        }

        .items thead th.qty,
        .items tbody td.qty {
            width: 40px;
            text-align: center;
        }

        .items thead th.rate,
        .items tbody td.rate,
        .items thead th.net,
        .items tbody td.net,
        .items thead th.amount,
        .items tbody td.amount {
            text-align: right;
            padding-right: 8px;
        }

        .items thead th.delivery,
        .items tbody td.delivery {
            white-space: normal;
            text-align: center;
        }

        .items tbody tr {
            page-break-inside: auto;
            break-inside: auto;
        }

        .bottom {
            margin-top: 8px;
            width: 100%;
            display: table;
            table-layout: fixed;
            border-collapse: collapse;
            box-sizing: border-box;
        }

        .terms {
            display: table-cell;
            vertical-align: top;
            width: 65%;
            box-sizing: border-box;
            border: 1px solid #eee;
            padding: 12px 12px 12px 14px;
            border-radius: 4px;
            font-size: 9.5px;
            color: #333;
            position: relative;
            overflow: hidden;
            page-break-inside: auto;
            break-inside: auto;
            -webkit-column-break-inside: auto;
            -moz-column-break-inside: auto;
            word-break: break-word;
            overflow-wrap: break-word;
        }

        .terms .bg-overlay {
            position: absolute;
            inset: 0;
            border-radius: 4px;
            pointer-events: none;
            z-index: 1;
        }

        .terms .content {
            position: relative;
            z-index: 2;
        }

        .summary {
            display: table-cell;
            vertical-align: top;
            width: 35%;
            box-sizing: border-box;
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #eee;
            font-size: 10px;
            color: #111;
            page-break-inside: avoid;
            break-inside: avoid;
            background: #fdf5e2;
        }

        .summary .row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 2px 0;
            border-bottom: 1px solid #eee;
        }

        .summary .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f58220;
            color: #fff;
            font-weight: 700;
            padding: 4px 6px;
            border-radius: 4px;
            margin-top: 6px;
        }

        .summary .in-words {
            font-size: 9px;
            color: #666;
            margin-top: 6px;
            text-align: right;
            word-wrap: break-word;
        }

        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }

        .right {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        .small {
            font-size: 9.5px;
            color: #666;
        }

        .terms ul {
            margin: 6px 0 0 14px;
            padding: 0;
            list-style: disc;
        }

        .terms li {
            margin-bottom: 4px;
            line-height: 1.25;
            color: #555;
            font-size: 9px;
        }
    </style>
</head>

<body>
    <div class="paper">
        {{-- <div style="width:100%;">
            <div class="company-name">{{ $company['name'] ?? '' }}</div>
        </div> --}}
        <table class="hdr" role="presentation">
            <tr>
                <td class="logo-col" style="width:18%; vertical-align:middle;">
                    <div class="logo" aria-hidden="true">
                        @if(!empty($company['logo']) && file_exists($company['logo']))
                            <img src="{{ $company['logo'] }}" alt="logo" />
                        @elseif(!empty($company['logo']))
                            <img src="{{ $company['logo'] }}" alt="logo" />
                        @else
                            <div class="logo-fallback">LOGO</div>
                        @endif
                    </div>
                </td>

                <td class="center-col" style="width:82%; vertical-align:middle;">
                    <div style="width:100%;">
                        <div class="company-meta">
                            <div class="meta-row">
                                <span class="small"><strong>Address:</strong>
                                    {{ trim($company['address_line1']) }}</span>
                                <span class="small"><strong>Tel:</strong> {{ trim($company['contact']) }}</span>
                                <span class="small"><strong>Email:</strong> {{ $company['email'] }}</span>
                                <span class="small"><strong>GSTIN:</strong> {{ $company['gstin'] }}</span>
                                <span class="small"><strong>A/C:</strong> {{ $company['account'] }}</span>
                                <span class="small"><strong>Bank:</strong> {{ $company['bank'] }}</span>
                                <span class="small"><strong>Branch:</strong> {{ $company['branch_name'] }}</span>
                                <span class="small"><strong>Udyam/MSME:</strong> {{ $company['udyam_no'] }}</span>
                                <span class="small"><strong>IFSC:</strong> {{ $company['ifsc'] }}</span>
                                <span class="small"><strong>Call:</strong> {{ $company['contact_person'] }}</span>
                            </div>
                        </div>

                        <span class="meta-row"
                            style="background:#fff2e6;padding:2px 6px;border-radius:3px;color:#b85c12;font-weight:700;margin-top:10px;">
                            <span class="small"><strong>Quotation No:</strong> {{ trim($quotation['no']) }}</span>
                            <span class="small"><strong>Date:</strong> {{ trim($quotation['date']) }}</span>
                            <span class="small"><strong>Ref:</strong> {{ $quotation['ref'] }}</span>
                            <span class="small"><strong>Contact Person:</strong> {{ $company['contact_person'] }}</span>
                        </span>

                    </div>
                </td>
            </tr>
        </table>

        <div class="authorised">
            <span style="display:inline-block; margin-left:6px;"><strong>Authorised For : </strong> Qualisil, Qaliseal,
                Gas World, Macherey Nagel,
                G.L.Science (GC Columns), S.A.S.Corporation, Nomura Chemicals (Develosil), Sielc (Primesep), Sciencix,
                Poly LC, MZAnalysentechnik, Sepax, Frontier Lab. Click <span
                    style="color:blue;">{{ $company['web'] ?? 'www.chromatographyworld.com' }}</span> for more
                details.</span>
        </div>

        <!-- SHIPPING -->
        <div class="ship-wrap">
            <table class="items" role="presentation">
                <colgroup>
                    <col style="width:12%" />
                    <col style="width:28%" />
                    <col style="width:12%" />
                    <col style="width:12%" />
                    <col style="width:10%" />
                    <col style="width:15%" />
                    <col style="width:15%" />
                    <col style="width:10%" />
                </colgroup>
                <thead>
                    <tr style="color:#fff">
                        <th>Company</th>
                        <th>Address</th>
                        <th>Contact</th>
                        <th>GSTN</th>
                        <th>City</th>
                        <th>Pincode</th>
                        <th>Mobile</th>
                        <th>Email</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ $shipping['company'] ?? '-' }}</td>
                        <td>{{ $shipping['address'] ?? '' }}</td>
                        <td>{{ $shipping['contact_person'] ?? '' }}</td>
                        <td class="break-all">{{ $shipping['gstn'] ?? '' }}</td>
                        <td>{{ $shipping['city'] ?? '' }}</td>
                        <td>{{ $shipping['pincode'] ?? '' }}</td>
                        <td>{{ $shipping['mobile'] ?? '' }}</td>
                        <td class="break-all">{{ $shipping['email'] ?? '' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- ITEMS -->
        <table class="items" role="presentation">
            <colgroup>
                <col class="serial-col" />
                <col style="width:210px" />
                <col style="width:70px" />
                <col style="width:40px" />
                <col style="width:60px" />
                <col style="width:46px" />
                <col style="width:60px" />
                <col style="width:60px" />
                <col style="width:80px" />
                <col style="width:90px" />
                <col style="width:100px" />
            </colgroup>
            <thead style="background:#fddcb4; color:#fff">
                <tr>
                    <th class="serial">#</th>
                    <th class="part">Part No.</th>
                    <th class="hsn">HSN</th>
                    <th class="qty">Qty</th>
                    <th class="price">Unit Price</th>
                    <th class="disc">Discount%</th>
                    <th class="net">Net Amount</th>
                    <th class="igst">IGST%</th>
                    <th class="igstamt">IGST Amount</th>
                    <th class="amount">Total</th>
                    <th class="delivery">Delivery Status</th>
                </tr>
            </thead>
            <tbody style="border: 0.5px solid #fddcb4; !important">
                @foreach($items as $it)
                    <tr>
                        <td class="serial">{{ trim($it['no'] ?? '') }}</td>
                        <td class="part">{{ $it['part_no'] ?? '' }}</td>
                        <td class="hsn">{{ $it['hsn_code'] ?? '' }}</td>
                        <td class="qty center">{{ $it['quantity'] ?? '' }}</td>
                        <td class="qty center">{{ $it['price'] ?? '' }}</td>
                        <td class="disc center">{{ $it['discount'] ?? '' }}</td>
                        <td class="net right">{{ $it['net_price'] ?? '' }}</td>
                        <td class="igst right">{{ $it['igst'] ?? '' }}</td>
                        <td class="igstamt right">{{ $it['total'] ?? '' }}</td>
                        <td class="amount right">{{ $it['total'] ?? '' }}</td>
                        <td class="delivery center">{{ $it['notes'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td class="serial"></td>
                        <td colspan="10" class="desc"><strong>Description:</strong> {{ $it['description'] ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- TERMS & SUMMARY -->
        <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size:10px;
      background-image: url('{{ $term_conditon_bg_img }}');
       background-size: cover; background-position: center right; background-repeat: no-repeat;">
            <tr>
                <!-- TERMS (left) -->
                <td valign="top" width="52%" style="padding:8px; vertical-align:top;">
                    <div style="padding:6px; border-radius:2px;">
                        <strong style="font-size:10px; display:block; margin-bottom:6px;">Terms &amp;
                            Conditions</strong>
                        <ul style="margin:0 0 0 12px; padding:0; font-size:9px; line-height:1.35;">
                            @foreach($terms as $t)
                                <li>{{ $t }}</li>
                            @endforeach
                        </ul>
                    </div>
                </td>

                <!-- SIGNATURE (center) -->
                <td valign="middle" width="26%" style="padding:8px; text-align:center;">
                    <div style="display:inline-block; width:100%; padding:10px 0;">
                        <div style="margin-bottom:6px;">
                            <strong style="font-size:10px; color:#b8860b;">For Chromatography World</strong>
                        </div>
                        <div style="margin-bottom:6px;">
                            <strong style="font-size:9px; color:#b8860b;">Authorized Signatory</strong>
                        </div>
                        <div>
                            <strong style="font-size:8px; color: black;">{{ $prepared_by }}</strong>
                        </div>
                    </div>
                </td>

                <!-- SUMMARY (right) -->
                <td valign="top" width="22%"
                    style="color: #e8f3ff, padding:8px; border-left:1px solid #e0e0e0; font-size:9px; line-height:1.35;">
                    <div>
                        <div><strong>Sub Unit Total:</strong> {{ $totals['sub_unit_total'] ?? '' }}</div>
                        <div><strong>Sub Net Total:</strong> {{ $totals['sub_net_total'] ?? '' }}</div>
                        <div><strong>Sub IGST Total:</strong> {{ $totals['total_igst_total'] ?? '' }}</div>
                        <div style="margin-top:6px; font-weight:700; font-size:10px; color:green;">
                            <strong>Grand Total:</strong> {{ $totals['grand_total'] ?? '' }}
                        </div>
                        <div style="margin-top:6px; font-size:8.5px; color:#444;">{{ $totals['in_words'] ?? '' }}</div>
                        <div style="margin-top:8px; font-size:8px; color:#555;">
                            We look forward to your valuable order! Thank you!
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <script type="text/php">
        if(isset($pdf)){
            $pdf->page_script('
                $font = $fontMetrics->get_font("Inter","normal");
                if(!$font){
                    $font = $fontMetrics->get_font("DejaVu Sans","normal");
                }
                $size = 9;
                $text = "Page $PAGE_NUM of $PAGE_COUNT";
                $width = $fontMetrics->get_text_width($text,$font,$size);
                $x = $pdf->get_width() - $width - 12;
                $y = $pdf->get_height() - 15;
                $pdf->text($x,$y,$text,$font,$size);
            ');
        }
    </script>
    </script>
</body>

</html>