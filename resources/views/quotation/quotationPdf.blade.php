<!doctype html>
<html>

<head>
    <meta charset="utf-8" />
    <title>Quotation</title>
    <style>
        body,
        table,
        th,
        td {
            font-family: "Inter", "DejaVu Sans", Arial, Helvetica, sans-serif;
            color: #222;
            font-size: 9px;
            line-height: 1.45;
            letter-spacing: 0.1px;
            border-collapse: collapse;
        }

        html,
        body {
            height: 100%;
            margin: 0;
            padding: 6px;
            background: #fff;
        }

        .paper {
            background: #fff;
            border-radius: 1px;
            padding: 2px;
            margin: 0 auto;
            max-width: 100%;
            box-sizing: border-box;
        }

        /* @page {
            margin: 6mm;
        } */

        /* --- Collapsed Table Core --- */
        table {
            width: 100%;
            border-collapse: collapse;
            border: 0.25 solid #f9d8ab;
            font-size: 9px;
            background: #fff;
        }

        th,
        td {
            border: 0.25 solid #f9d8ab;
            padding: 3px 5px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: linear-gradient(to bottom, #fde3c1, #fddcb4);
            font-weight: 600;
            color: #2e2e2e;
            text-transform: uppercase;
        }

        tr:nth-child(even) {
            background: #fffaf5;
        }

        tr:hover td {
            background: #fff1df;
        }

        /* --- Header Table --- */
        .hdr {
            width: 100%;
            border: 0.25 solid #f9d8ab;
            background: #fdf1cb;
        }

        .logo-col {
            width: 18%;
            vertical-align: middle;
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
            justify-content: center;
            overflow: hidden;
        }

        .logo img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .logo-fallback {
            font-size: 12px;
            font-weight: 700;
            color: #fff;
            background: #0a2a30;
            text-align: center;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* --- Authorised Section --- */
        .authorised {
            margin-top: 8px;
            border: 0.25 solid #fddcb4;
            background: #fffdf7;
            padding: 8px;
            font-size: 9px;
            color: #333;
            text-align: center;
        }

        /* --- Shipping Table --- */
        .ship-wrap {
            border-collapse: collapse;
            border: 0.25 solid #fddcb4;
            background: #fafafa;
            margin-top: 8px;
            margin-bottom: 8px;
        }

        .ship-wrap th {
            background: #fddcb4;
            color: #222;
        }

        /* --- Item Table --- */
        .items th {
            background: #fddcb4;
            border: 0.25 solid #c7a86a;
        }

        .items td {
            border: 0.25 solid #ddd;
        }

        .items tbody tr:nth-child(odd) {
            background: #fff;
        }

        .items tbody tr:nth-child(even) {
            background: #fef9f2;
        }

        .desc {
            background: #fdf7ea;
            border-top: none;
            font-style: italic;
        }

        /* --- Terms and Summary --- */
        .terms {
            border: 0.25 solid #ddd;
            padding: 8px;
            background: #fdfcf9;
            font-size: 9px;
        }

        .terms ul {
            margin: 6px 0 0 14px;
            padding: 0;
            list-style: disc;
        }

        .terms li {
            margin-bottom: 3px;
            color: #555;
        }

        .summary {
            border: 0.25 solid #ccc;
            padding: 8px;
            background: #fff9f3;
            font-size: 9px;
        }

        .summary .row {
            display: flex;
            justify-content: space-between;
            padding: 2px 0;
        }

        .summary .total-row {
            background: #f58220;
            color: #fff;
            padding: 4px 6px;
            font-weight: 700;
            margin-top: 6px;
        }

        .summary .in-words {
            font-size: 8.5px;
            color: #444;
            margin-top: 6px;
            text-align: right;
        }

        /* --- Print Fallback --- */
        @media print {

            th,
            td {
                border: 0.25 solid #bbb !important;
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
                        <span class="small"><strong>Click :</strong>
                            <span style="color:blue;">{{ $company['web'] ?? 'www.chromatographyworld.com' }} for
                                more
                                details</span></span>


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
                </td>
            </tr>
        </table>

        <div class="authorised">
            <span style="display:inline-block; margin-left:6px;"><strong>Authorised For : </strong> Qualisil, Qaliseal,
                Gas World, Macherey Nagel,
                G.L.Science (GC Columns), S.A.S.Corporation, Nomura Chemicals (Develosil), Sielc (Primesep), Sciencix,
                Poly LC, MZAnalysentechnik, Sepax, Frontier Lab.
        </div>

        <!-- SHIPPING -->
        <div class="ship-wrap">
            <table role="presentation">
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
        <table class="ship-wrap" role="presentation">
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
                @foreach($items as $k => $it)
                    <tr>
                        <td class="serial">{{ trim($k + 1 ?? '') }}</td>
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
       background-size: cover; background-position: center right; background-repeat: no-repeat; margin-top: 8px;">
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
                    style="padding:8px; border-left:0.25 solid #e0e0e0; font-size:9px; line-height:1.35;">
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
            <tr>;
                <td colspan="3" style="text-align:center; padding:5px; font-size:12px; color:red">
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