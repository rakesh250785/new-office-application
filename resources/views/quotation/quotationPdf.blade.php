<!doctype html>
<html>

<head>
    <meta charset="utf-8" />
    <title>Quotation</title>
    <style>
        /* Base */
        body,
        table,
        td,
        th {
            font-family: "Inter", "DejaVu Sans", Arial, Helvetica, sans-serif;
            color: #222;
            font-size: 9px;
            line-height: 1.45;
            letter-spacing: 0.1px;
        }

        html,
        body {
            height: 100%;
            margin: 0;
            padding: 6px;
            background: #ffffff;
            color: #222;
            font-size: 10px;
        }

        .paper {
            background: #fff;
            border-radius: 0.5px;
            padding: 2px;
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

        .title {
            color: #e76b00;
            font-size: 16px;
            font-weight: 700;
            margin: 0 0 4px 0;
        }

        .authorised {
            margin-top: 5px;
            border: 0.5px solid #eef2f4;
            background: #fff;
            padding: 8px;
            border-radius: 4px;
            font-size: 9px;
            color: #333;
            text-align: center;
            line-height: 1.2;
            word-break: break-word;
            display: block;
        }

        .ship-wrap {
            border-radius: 1px;
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

        /* Table defaults */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 12px;
        }

        th,
        td {
            padding: 2px 2px;
            text-align: left;
            vertical-align: top;
            word-break: break-word;
        }

        th {
            background: #f2f2f2;
            font-weight: bold;
            color: #333;
            /* softer default heading color */
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

        /* Items styling (updated) */
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

        /* professional header color + very light borders */
        .items thead th {
            background: #fddcb4;
            color: #2e2e2e;
            /* deep graphite — softer than black */
            padding: 4px 6px;
            font-weight: 700;
            font-size: 9px;
            line-height: 1.1;
            border: 0.6px solid rgba(0, 0, 0, 0.06);
            /* slightly stronger hairline for headers */
            text-align: left;
            vertical-align: middle;
            letter-spacing: 0.2px;
        }

        /* very-light, consistent borders for body cells */
        .items tbody td {
            padding: 4px 6px;
            border: 0.4px solid rgba(0, 0, 0, 0.04);
            /* whisper-light */
            vertical-align: top;
            font-size: 10px;
            color: #222;
            font-family: "Inter", "DejaVu Sans", Arial, Helvetica, sans-serif;
            background-clip: padding-box;
        }

        .items .desc {
            color: #666;
            font-size: 9.5px;
            padding-left: 2px;
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
            text-align: left;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-weight: 700;
        }

        .items thead th.hsn,
        .items tbody td.hsn {
            width: 70px;
        }

        .items thead th.qty,
        .items tbody td.qty {
            width: 40px;
            text-align: left;
        }

        .items thead th.rate,
        .items tbody td.rate,
        .items thead th.net,
        .items tbody td.net,
        .items thead th.amount,
        .items tbody td.amount {
            padding-right: 2px;
        }

        .items thead th.delivery,
        .items tbody td.delivery {
            white-space: normal;
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
            border: 0.5px solid rgba(0, 0, 0, 0.05);
            border-radius: 2px;
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
            padding: 8px;
            background: transparent;
        }

        .terms .bg-overlay {
            position: absolute;
            inset: 0;
            border-radius: 2px;
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
            border-radius: 2px;
            border: 0.6px solid rgba(0, 0, 0, 0.05);
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
            border-bottom: 0.5px solid #eee;
        }

        .summary .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f58220;
            color: #fff;
            font-weight: 700;
            padding: 2px 2px;
            border-radius: 2px;
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

        /* Accessibility / print fallback: if hairlines disappear in PDF engine, use faint grey */
        @media print {

            .items tbody td,
            .items thead th {
                border: 1px solid rgba(0, 0, 0, 0.06);
            }
        }
    </style>
</head>

<body>
    <div class="paper">
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
                                <span class="small"
                                    style="background:#fff2e6;padding:2px;border-radius:3px;color:#b85c12;font-weight:700;margin-top:10px;"><strong>Quotation
                                        No:</strong> {{ trim($quotation['no']) }}</span>
                                <span class="small"
                                    style="background:#fff2e6;padding:2px;border-radius:3px;color:#b85c12;font-weight:700;margin-top:10px;"><strong>Date:</strong>
                                    {{ trim($quotation['date']) }}</span>
                                <span class="small"
                                    style="background:#fff2e6;padding:2px;border-radius:3px;color:#b85c12;font-weight:700;margin-top:10px;"><strong>Ref:</strong>
                                    {{ $quotation['ref'] }}</span>
                            </div>
                        </div>
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
                    <tr style="color:#fff; text-align: center;">
                        <th>To Company</th>
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
                <col style="width:90px" />
            </colgroup>
            <thead style="background:#fddcb4; color:#fff;">
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
            <!-- removed inline border style; CSS handles hairlines -->
            <tbody>
                @foreach($items as $it)
                    <tr>
                        <td class="serial">{{ trim($it['no'] ?? '') }}</td>
                        <td class="part">{{ $it['part_no'] ?? '' }}</td>
                        <td class="hsn">{{ $it['hsn_code'] ?? '' }}</td>
                        <td class="qty">{{ $it['quantity'] ?? '' }}</td>
                        <td class="qty">{{ $it['price'] ?? '' }}</td>
                        <td class="disc">{{ $it['discount'] ?? '' }}</td>
                        <td class="net">{{ $it['net_price'] ?? '' }}</td>
                        <td class="igst">{{ $it['igst'] ?? '' }}</td>
                        <td class="igstamt">{{ $it['total'] ?? '' }}</td>
                        <td class="amount ">{{ $it['total'] ?? '' }}</td>
                        <td class="delivery">{{ $it['notes'] ?? '' }}</td>
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
                <td valign="top" width="52%" style="padding:1px; vertical-align:top;">
                    <div style="padding:2px; border-radius:2px;">
                        <strong style="font-size:10px; margin-bottom: -100px;">Terms &amp;
                            Conditions</strong>
                        <span style="padding:0; font-size:9px; line-height:0.5;">
                            {!! $terms !!}
                        </span>
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
                    style="padding:8px; border-left:1px solid #e0e0e0; font-size:9px; line-height:1.35;">
                    <div>
                        <div><strong>Sub Unit Total ( {{$currency['name'] ?? ''}} ) :</strong>
                            {{ $totals['sub_unit_total'] ?? '' }}</div>
                        <div><strong>Sub Net Total ( {{$currency['name'] ?? ''}} ) :</strong>
                            {{ $totals['sub_net_total'] ?? '' }}</div>
                        <div><strong>Sub IGST Total ( {{$currency['name'] ?? ''}} ) :</strong>
                            {{ $totals['total_igst_total'] ?? '' }}
                        </div>
                        <div style="margin-top:6px; font-weight:700; font-size:10px; color:green;">
                            <strong>Grand Total {{$currency['name'] ?? ''}}:</strong> {{ $totals['grand_total'] ?? '' }}
                        </div>
                        <div style="margin-top:6px; font-size:8.5px; color:#444;">{{ $totals['in_words'] ?? '' }}</div>
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="3" style="text-align:center; padding:5px; font-size:12px;">
                    We look forward to your valuable order! Thank you.
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
</body>

</html>