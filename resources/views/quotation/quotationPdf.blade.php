<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Quotation</title>
    <style>
        /* ================= FONT EMBED (PDF SAFE) ================= */
        @font-face {
            font-family: 'Calibri';
            src: url("/fonts/calibri.ttf") format("truetype");
            font-weight: normal;
        }

        @font-face {
            font-family: 'Calibri';
            src: url("/fonts/calibri.ttf") format("truetype");
            font-weight: bold;
        }

        @font-face {
            font-family: 'Calibri';
            src: url("/fonts/calibri.ttf") format("truetype");
            font-style: italic;
        }

        @font-face {
            font-family: 'Calibri';
            src: url("/fonts/calibri.ttf") format("truetype");
            font-weight: bold;
            font-style: italic;
        }

        /* ================= GLOBAL BORDER CONTROL ================= */
        :root {
            --border-color: #c8c8c8;
            --border-thin: 0.4px;
            --border-light: 0.2px;
        }

        /* ================= BASE ================= */
        body,
        table,
        th,
        td {
            font-family: "Calibri", Arial, Helvetica, sans-serif;
            font-size: 11px;
            line-height: 1.5;
            color: #1f1f1f;
            font-variant-numeric: tabular-nums;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            background: #fff;
        }

        /* ================= PAGE ================= */
        @page {
            margin: 5mm;
        }

        .paper {
            box-sizing: border-box;
        }

        /* ================= TABLE CORE ================= */
        table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
            table-layout: auto;
        }

        /* ================= UNIFORM BORDER SYSTEM ================= */
        th,
        td {
            border: var(--border-thin) solid var(--border-color);
            padding: 2px;
            vertical-align: top;
            word-break: break-word;
            overflow-wrap: anywhere;
            box-sizing: border-box;
        }

        /* Close left edge */
        table tr th:first-child,
        table tr td:first-child {
            border-left: var(--border-thin) solid var(--border-color);
        }

        /* Close top edge */
        table thead tr:first-child th,
        table tbody tr:first-child td {
            border-top: var(--border-thin) solid var(--border-color);
        }

        /* ================= HEADER ================= */
        .hdr,
        .hdr tr,
        .hdr td {
            border: none !important;
            margin: 0;
            padding: 0;
        }

        .hdr img {
            display: block;
            height: 60px;
        }

        .hdr p,
        .hdr div,
        .hdr span {
            margin: 0;
            padding: 0;
            line-height: 1.2;
        }

        /* ================= META ================= */
        .meta-label {
            color: #004f6e;
        }

        .meta-label.alert {
            color: #b30f16;
        }

        .meta-link {
            color: #0b57d0;
            text-decoration: underline;
        }

        /* ================= HTML EDITOR HARD RESET ================= */
        .spec-row td,
        .comment-row td,
        .notes td,
        .items td.description,
        .items td.heading,
        .items td.html-content {
            line-height: 1 !important;
        }

        .spec-row td p,
        .comment-row td p,
        .notes td p,
        .items td.description p,
        .items td.heading p,
        .items td.html-content p {
            margin: 0 !important;
            padding: 0 !important;
        }

        .spec-row td p:empty,
        .comment-row td p:empty,
        .notes td p:empty,
        .items td div:empty {
            display: none !important;
        }

        .spec-row td br,
        .comment-row td br,
        .notes td br,
        .items td.description br,
        .items td.heading br,
        .items td.html-content br {
            display: none !important;
        }

        .spec-row td div,
        .comment-row td div,
        .notes td div,
        .items td.description div,
        .items td.heading div,
        .items td.html-content div,
        .spec-row td span,
        .comment-row td span {
            margin: 0 !important;
            padding: 0 !important;
            line-height: 1.2 !important;
        }

        .spec-row td ul,
        .spec-row td ol,
        .notes td ul,
        .notes td ol,
        .comment-row td ul,
        .comment-row td ol {
            margin: 0 !important;
            padding-left: 14px !important;
        }

        .spec-row td li,
        .notes td li,
        .comment-row td li {
            margin: 0 !important;
            padding: 0 !important;
            line-height: 1 !important;
        }

        .spec-row td,
        .notes td,
        .comment-row td {
            padding-top: 2px !important;
            padding-bottom: 2px !important;
        }


        /* ================= AUTHORISED ================= */
        .authorised {
            background: #2b2b2b;
            color: #f5f2e8;
            text-align: center;
            font-size: 12px;
            font-weight: 700;
            padding: 2px;
        }

        .authorised td {
            border: none !important;
        }

        /* ================= SHIPPING TABLE ================= */
        .ship {
            background: #f2f8f9;
            border-collapse: collapse;
        }

        .ship th,
        .ship td {
            font-size: 11px;
            padding: 0 4px;
            vertical-align: middle;
        }

        .ship tr,
        .ship th,
        .ship td {
            border: var(--border-light) solid var(--border-color);
        }

        .ship th {
            color: #004f6e;
            white-space: nowrap;
            text-align: left;
        }

        /* ================= ITEMS ================= */
        .items {
            margin-top: 7px;
        }

        .items th {
            background: #eef6f9;
            color: #004f6e;
            font-size: 12px;
            text-align: left;
        }

        .items tbody td {
            font-size: 11px;
        }

        .items tbody tr.odd td {
            background: #fdecec;
        }

        .items tbody tr.even td {
            background: #f6d6d6;
        }

        /* ================= TOTAL ROW ================= */
        .items tbody tr.total-row td {
            background: #333 !important;
            color: #f5f2e8;
            font-size: 14px;
            border: none !important;
            font-weight: 700;
        }

        /* ================= MONEY ================= */
        .items th.money,
        .items td.money {
            white-space: nowrap;
            text-align: left;
            padding: 2px;
        }

        /* ================= HTML CONTENT RESET ================= */
        .html-content * {
            margin: 0 !important;
            padding: 0 !important;
            line-height: 1.2 !important;
            text-align: justify;
        }

        .html-content ul,
        .html-content ol {
            padding-left: 14px !important;
        }

        .html-content li {
            margin-bottom: 2px !important;
        }

        /* ================= LABEL BLOCK ================= */
        .label-heading {
            font-size: 12px;
            font-weight: 700;
            color: #006c95;
            line-height: 1;
            margin-bottom: -15px;
        }

        .detail-text {
            font-size: 10px;
            line-height: 1;
        }
    </style>

</head>

<body>

    @php
        $currency = $currency->name ?? '';
        $isMH = ($shipping['state'] ?? '') === 'Maharashtra';
        $isGW = ($quotation['quotation_type'] ?? '') === 'GW';
    @endphp

    @php
        $isGW = ($quotation['quotation_type'] === 'GW');
        $isMH = ($shipping['state'] === 'Maharashtra');

        if (!$isGW && $isMH) {
            $noteColspan = 14;
        } else if (!$isGW && !$isMH) {
            $noteColspan = 12;
        } else if ($isGW && !$isMH) {
            $noteColspan = 14;
        } elseif ($isGW && $isMH) {
            $noteColspan = 16;
        }

    @endphp

    <div class="paper">

        {{-- ================= HEADER ================= --}}
        <table class="hdr" width="100%" cellpadding="0" cellspacing="0" style="
    border-collapse:collapse;
    width:100%;
    background:#cfeef9;
    
">
            <tr>
                <!-- LEFT : LOGO + ISO -->
                <td style="
            width:18%;
            text-align:center;
            vertical-align:top;
            padding:6px 4px;
              
        ">
                    @if(!empty($company['logo']))
                        <img src="{{ $company['logo'] }}"
                            style="max-width:200px; height:auto; display:block; margin:0 auto;margin-top: -17px">
                    @else
                        <div style=" font-size:14px;">QUOTATION</div>
                    @endif

                    <div style="
                        margin-top: 0px;
                        font-size:12px;
                        color:#111;
                        font-weight: 700;
                    ">
                        ISO 9001-2015
                    </div>
                </td>

                <!-- RIGHT : DETAILS -->
                <td style="
                    vertical-align: top;
                    padding: 6px 8px;
                    font-size: 12px;     
                    line-height: 1.5;
                    line-spacing: 1.5;
                    color: #111;
                    font-weight: 700;
                ">
                    <!-- 1️⃣ FIRST LINE : ADDRESS -->
                    <b style="font-size:12px;color:#004f6e;">Address :</b>
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 217, 2nd Floor, Champaklal Industrial Estate, Sion East,
                    Mumbai – 400022, India
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    <b style="font-size:11px;color:#004f6e;">Call :</b>
                    &nbsp;&nbsp; 91-022-43159100
                    <br>

                    <!-- 2️⃣ SECOND LINE : EMAILS + CALL -->
                    <b style="font-size:12px;color:#004f6e;">Email :</b>
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    sales@chromatographyworld.com,&nbsp;&nbsp;&nbsp;&nbsp;
                    speed@chromatographyworld.com,&nbsp;&nbsp;&nbsp;&nbsp;
                    gm-support@chromatographyworld.com
                    &nbsp;&nbsp;

                    <br>

                    <!-- 3️⃣ THIRD LINE : GST + MSME + WEBSITE -->
                    <b style="font-size:12px;color:#800000;">GSTN :</b>
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 27AAGFC1217K1ZM
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    <b style="font-size:12px;color:#800000;">Udyam / MSME :</b>
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; UDYAM-MH-19-0078510
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    <b style="font-size:12px;color:#004f6e;">Web :</b>
                    <span style="color:#0b57d0;">&nbsp;&nbsp;&nbsp;&nbsp; www.chromatographyworld.com</span>
                    <br>

                    <!-- 4️⃣ FOURTH LINE : BANK DETAILS -->
                    <b style="font-size:12px;color:#004f6e;">Bank :</b>
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Kotak Mahindra Bank
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    <b style="font-size:12px;color:#004f6e;">Branch :</b>
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Matunga
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    <b style="font-size:12px;color:#004f6e;">IFSC :</b>
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; KKBK0000644
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    <b style="font-size:12px;color:#004f6e;">A/C :</b>
                    &nbsp;&nbsp;&nbsp;&nbsp; 4611234274
                </td>
            </tr>
        </table>


        {{-- AUTHORISED --}}
        <div class="authorised">
            <b style="font-size:12px;line-height:1.35;">Authorised For :</b>
            Qualisil, Qaliseal, Gas World, Macherey Nagel,
            G.L. Science (GC Columns), S.A.S. Corporation,
            Nomura Chemicals (Develosil), Sielc (Primesep),
            Sciencix, Poly LC, MZ Analysentechnik, Sepax, Frontier Lab.
            Click
            <b style="
                color: #fff2cc;
                text-decoration: underline;
                font-size: 14px;
              ">
                www.chromatographyworld.com
            </b>
            for more details.
        </div>


        <h4 style="
        text-align:center;
        color:#800000;
        font-size:18px;
        margin:2px 0;
        vertical-align: middle;
        letter-spacing:0.3px;
    ">
            Quotation
        </h4>


        {{-- ================= SHIPPING ================= --}}
        <table class="ship">
            <tr>
                <th>Company</th>
                <td colSpan="6">{{ $shipping['company'] }}</td>
                <th>GSTN</th>
                <td colSpan="3">{{ $shipping['gstn'] }}</td>
            </tr>

            <tr>
                <th>Address</th>
                <td colspan="2">{{ $shipping['address'] }}</td>
                <th>Pin Code</th>
                <td>{{ $shipping['pincode'] }}</td>
                <th>City</th>
                <td>{{ $shipping['city'] }}</td>
                <th>State</th>
                <td>{{ $shipping['state'] }}</td>
                <th>Country</th>
                <td>{{ $shipping['country'] }}</td>

            </tr>
            <tr>
                <th>Contact Person</th>
                <td>{{ $shipping['contact_person'] }}</td>
                <th>Email</th>
                <td colSpan="2">{{ $shipping['email'] }}</td>
                <th>Landline</th>
                <td colSpan="2">{{ $shipping['landline'] }}</td>
                <th>Mobile</th>
                <td colSpan="2">{{ $shipping['mobile'] }}</td>
            </tr>

            <tr>
                <th>Quotation No.</th>
                <td colSpan="2">{{ $quotation['no'] }}</td>
                <th>Quotation Date</th>
                <td colSpan="2">{{ $quotation['date'] }}</td>
                <th>Enq. Ref.</th>
                <td colSpan="4">{{ $quotation['ref'] }}</td>
            </tr>
        </table>

        {{-- ================= ITEMS ================= --}}
        <table class="items">

            <tbody>
                <tr>
                    <th>Sr</th>
                    <th>Part No.</th>
                    <th>Description</th>

                    @if($isGW)
                        <th>Maker</th>
                        <th>UOM</th>
                    @endif

                    <th class="money">HSN</th>
                    <th>Qty</th>
                    <th class="money">Unit Price ({{ $currency }})</th>
                    <th>Disc %</th>
                    <th>Net Price ({{ $currency }})</th>

                    @if($isMH)
                        <th>SGST %</th>
                        <th>SGST AMT</th>
                        <th>CGST %</th>
                        <th>CGST AMT</th>
                    @else
                        <th>IGST %</th>
                        <th>IGST AMT</th>
                    @endif

                    <th>Total ({{ $currency }})</th>
                    <th>Delivery</th>
                </tr>


                @php
                    $hasText = function ($v) {
                        return !empty(trim(preg_replace('/<[^>]*>|&nbsp;/', '', $v ?? '')));
                    };
                @endphp
                @foreach($items as $i => $it)
                    @php
                        $net = (float) $it['net_price'];
                        $igst = (float) $it['igst'];
                        $half = $igst / 2;
                        $igstAmt = $net * $igst / 100;
                        $halfAmt = $net * $half / 100;
                        $rowTotal = $isMH ? $net + $halfAmt + $halfAmt : $net + $igstAmt;
                        $rowClass = ($i % 2 === 0) ? 'odd' : 'even';
                        $showDetails =
                            ($isGW && ($hasText($it['specification'] ?? null)))
                            || $hasText($it['product_specification'] ?? null);
                    @endphp


                    <tr class="item-row {{ $rowClass }}">
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $it['part_no'] }}</td>
                        <td>{!! $it['description'] !!}</td>

                        @if($isGW)
                            <td>{{ $it['principal']['type'] ?? $it['principal'] ?? '' }}</td>
                            <td>{{ is_array($it['uom'] ?? null) ? ($it['uom']['uom'] ?? '') : ($it['uom'] ?? '') }}</td>
                        @endif

                        <td class="money">{{ $it['hsn_code'] }}</td>
                        <td>{{ $it['quantity'] }}</td>
                        <td class="money">{{ number_format($it['price'], 2) }}</td>
                        <td>{{ $it['discount'] }}</td>
                        <td>{{ number_format($net, 2) }}</td>

                        @if($isMH)
                            <td>{{ number_format($half, 2) }}</td>
                            <td class="money">{{ number_format($halfAmt, 2) }}</td>
                            <td>{{ number_format($half, 2) }}</td>
                            <td class="money">{{ number_format($halfAmt, 2) }}</td>
                        @else
                            <td>{{ number_format($igst, 2) }}</td>
                            <td class="money">{{ number_format($igstAmt, 2) }}</td>
                        @endif

                        <td class="money">{{ number_format($rowTotal, 2) }}</td>
                        <td class="notes">{!! $it['notes'] ?? '' !!}</td>
                    </tr>

                    @if($showDetails)
                        <tr class="item-row {{ $rowClass }}">
                            <td></td>
                            <td colspan="{{ $noteColspan - 1 }}" style="padding:4px 6px;">
                                <span style="display:flex; flex-direction:column; gap:2px;font-size:10px">
                                    @if($hasText($it['product_specification'] ?? null))
                                        <span class="detail-text  html-content">
                                            <b class="label-heading">
                                                Comments:
                                            </b>&nbsp;
                                            <span>{!! $it['product_specification'] !!}</span>
                                        </span>
                                    @endif
                                    @if($isGW && $hasText($it['specification'] ?? null))
                                        <span class="detail-text  html-content">
                                            <b class="label-heading">
                                                Specification with Heading:
                                            </b>&nbsp;
                                            <span>{!! $it['specification'] !!}</span>

                                        </span>
                                    @endif
                                </span>
                            </td>
                        </tr>
                    @endif
                @endforeach

                {{-- TOTAL --}}
                <tr class="total-row">
                    <td></td>
                    <td></td>
                    <td></td>
                    <td colspan="{{ $isGW ? 6 : 4 }}" class="label">
                        Grand Total ({{ $currency }})
                    </td>

                    <td class="money">{{ number_format($totals['sub_net_total'], 2) }}</td>

                    @if($isMH)
                        <td></td>
                        <td class="money">{{ number_format(($totals['grand_total'] - $totals['sub_net_total']) / 2, 2) }}
                        </td>
                        <td></td>
                        <td class="money">{{ number_format(($totals['grand_total'] - $totals['sub_net_total']) / 2, 2) }}
                        </td>
                    @else
                        <td></td>
                        <td class="money">{{ number_format($totals['total_igst_total'], 2) }}</td>
                    @endif

                    <td class="money">{{ number_format($totals['grand_total'], 2) }}</td>
                    <td></td>
                </tr>

                @if(!empty($product_description))
                    <tr style="background-color: #C9F1FF">
                        <td colspan="{{ $noteColspan }}" style="border:none; padding:6px 4px;">
                            <b>NOTES :</b> {!! $product_description!!}
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>

        {{-- ================= FOOTER ================= --}}
        <table width="100%" cellpadding="0" cellspacing="0" style="
            border-collapse:collapse;
            border:0;
            outline:0;
            box-shadow:none;
            font-size:12px;
            margin-top:6px;

            background-image: url('{{ $term_conditon_bg_img }}');
            background-repeat: no-repeat;
            background-position: center;
            background-size: 100% 100%;
        ">

            <!-- TERMS + SIGNATORY -->
            <tr style="border:0;">
                <!-- LEFT : TERMS -->
                <td width="72%" valign="top" style="
            padding:5px 7px;
            line-height:1.25;
            background: transparent;
            border:0;
        ">

                    <div style="
                
                color:#004f6e;
                margin-bottom:3px;
                font-weight: 700;
                font-size:12px;
            ">
                        Terms & Conditions:
                    </div>

                    <div style="line-height:1.25; border:0;">
                        <div class="terms" style="border:0;">
                            {!! $terms !!}
                        </div>
                    </div>
                </td>

                <!-- RIGHT : SIGNATORY -->
                <td width="28%" valign="top" style="
                    padding:5px 7px;
                    text-align:right;
                    line-height:1.25;
                    background: transparent;
                    border:0;
                    font-weight: 700;
                    font-size:12px;
                ">
                    <div style=" color:#004f6e;">
                        For Chromatography World
                    </div>

                    <div style=" color:#004f6e; margin-top:4px;">
                        Authorized Signatory
                    </div>

                    <div style="margin-top:6px;">
                        {{ $prepared_by }}
                    </div>
                </td>
            </tr>



        </table>
        <table width="100%" cellpadding="0" cellspacing="0" style="
        border-collapse:collapse;
        border:0;
        outline:0;
        box-shadow:none;
        font-size:10px;
        margin-top:6px;
        background-repeat: no-repeat;
        background-position: center;
        background-size: 100% 100%;
    ">

            <!-- FOOT STRIP -->
            <tr style="border:0;">
                <td colspan="2" style="
                background:#c9f1ff;
                text-align:center;
                padding:6px 8px;
                font-size:13px;
                border:0;
            ">
                    <div style="margin-bottom:2px;">
                        {{ $branch_address }}
                    </div>

                    <div style="
                    color:maroon;
                    font-size:13px;
                    font-weight: 700;
                ">
                        We Look Forward To Your Valuable Order! &nbsp; Thank You!
                    </div>
                </td>
            </tr>
        </table>

    </div>


    <script type="text/php">
        if (isset($pdf) && isset($fontMetrics)) {
        
            /* Resolve font ONCE */
            $pageFont = $fontMetrics->get_font("Calibri", "bold")
                        ?: $fontMetrics->get_font("DejaVu Sans", "bold");
        
            $pdf->page_script(function ($pageNum, $pageCount, $canvas, $fontMetrics) use ($pageFont) {
        
                $size = 11;
                $text = "Page $pageNum of $pageCount";
        
                $width = $fontMetrics->get_text_width($text, $pageFont, $size);
        
                $x = $canvas->get_width() - $width - 18;
                $y = $canvas->get_height() - 18;
        
                $canvas->text($x, $y, $text, $pageFont, $size);
            });
        }
    </script>


</body>

</html>