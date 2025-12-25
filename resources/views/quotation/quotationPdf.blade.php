<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Quotation</title>
    <style>
        /* ================= BASE ================= */
        body,
        table,
        th,
        td {
            font-family: "DejaVu Sans", Arial, Helvetica, sans-serif;
            font-size: 12.2px;
            line-height: 1.5;
            font-weight: 400;
            color: #1f1f1f;
            border-collapse: collapse;
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
            margin: 15mm;
        }

        .paper {
            box-sizing: border-box;
            padding: 6mm;
        }

        /* ================= TABLE ================= */
        table {
            border-collapse: collapse;
            table-layout: auto;
            width: calc(100% - 0.01px);
        }

        /* ================= CELLS ================= */
        th,
        td {
            border: 0.35px solid rgba(0, 0, 0, 0.18);
            padding: 4px;
            vertical-align: top;
            white-space: normal;
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        tr {
            page-break-inside: avoid;
        }

        /* ================= HEADER ================= */
        .hdr {
            background: #bcecfa;
            width: 100%;
            border: none !important;
            /* ✅ remove outer border */
        }

        .hdr tr,
        .hdr td {
            border: none !important;
            /* ✅ remove cell borders */
        }



        .hdr td {
            color: #333;
            padding: 6px 8px;
            line-height: 1.35;
            font-weight: 500;
        }

        /* ✅ remove extra left gap */
        .hdr td:first-child {
            padding-left: 0;
        }

        /* ================= META ================= */
        .meta-label {
            color: #006c95;
            font-weight: 600;
            letter-spacing: 0.2px;
        }

        .meta-label.alert {
            color: #b30f16;
        }

        .meta-link {
            color: #0b57d0;
            text-decoration: underline;
        }

        /* ================= AUTHORISED ================= */
        .authorised {
            background: #2b2b2b;
            color: #f5f2e8;
            text-align: center;
            font-size: 12px;
            font-weight: 400;
            line-height: 1.4;
            letter-spacing: 0.25px;
        }

        .authorised b {
            font-weight: 600;
        }

        /* ================= SHIPPING ================= */
        .ship {
            background: #f2f8f9;
            margin-bottom: 7px;
        }

        .ship th {
            color: #004f6e;
            font-weight: 600;
            font-size: 12px;
            text-align: left;
            white-space: nowrap;
            padding: 2px 4px;
        }

        .ship td {
            color: #222;
            font-weight: 500;
            padding: 2px 4px;
        }

        /* ================= ITEMS ================= */
        .items {
            margin-bottom: 7px;
        }

        .items th {
            background: #eef6f9;
            color: #004f6e;
            font-weight: 600;
            font-size: 12px;
            text-align: left;
        }

        /* RESET */
        .items tbody tr td {
            background: #ffffff;
        }

        /* OLD PDF STYLE */
        .items tbody tr.odd td {
            background: #fff2f2;
        }

        .items tbody tr.even td {
            background: #f2caca;
        }

        /* SPEC ROW */
        .items tbody tr.spec-row td {
            font-size: 11.6px;
            line-height: 1.4;
        }

        /* COMMENT ROW */
        .items tbody tr.comment-row td {
            font-size: 11.6px;
            line-height: 1.4;
        }

        /* ================= TOTAL ================= */
        .items tbody tr.total-row td {
            background: #333 !important;
            color: #f5f2e8;
            font-size: 14.5px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }

        .total-row .label {
            text-align: center;
        }

        .total-row .grand-total {
            font-size: 18.5px;
            font-weight: 700;
        }

        /* ================= FOOTER ================= */
        .footer-title {
            color: #006c95;
            font-weight: 700;
            font-size: 13.2px;
            letter-spacing: 0.2px;
        }

        .html-content ul,
        .html-content ol {
            padding-left: 18px;
            margin: 6px 0;
        }

        .html-content li {
            margin-bottom: 6px;
        }

        .terms,
        .terms * {
            font-size: 10.6px;
            line-height: 1.25 !important;
            color: #2b2b2b;
        }

        .terms p,
        .terms li {
            margin: 0 !important;
            padding: 0 !important;
        }

        .terms ul,
        .terms ol {
            margin: 0 !important;
            padding-left: 14px !important;
        }

        .terms br {
            display: none;
        }

        /* =========================================================
   HTML EDITOR CONTENT — HARD RESET (SPEC / COMMENT / DESC)
   ========================================================= */

        /* Scope ONLY editor-driven content */
        .spec-row td,
        .comment-row td,
        .notes td,
        .items td.description,
        .items td.heading,
        .items td.html-content {
            line-height: 1.2 !important;
        }

        /* Kill editor default blocks */
        .spec-row td p,
        .comment-row td p,
        .notes td p,
        .items td.description p,
        .items td.heading p,
        .items td.html-content p {
            margin: 0 !important;
            padding: 0 !important;
        }

        /* Kill empty editor rows */
        .spec-row td p:empty,
        .comment-row td p:empty,
        .notes td p:empty,
        .items td div:empty {
            display: none !important;
        }

        /* Remove <br> spacing injected by editor */
        .spec-row td br,
        .comment-row td br,
        .notes td br,
        .items td.description br,
        .items td.heading br,
        .items td.html-content br {
            display: none !important;
        }

        /* Neutralize div/span spacing */
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

        /* Lists — compact but readable */
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
            line-height: 1.2 !important;
        }

        /* Bold / inline formatting */
        .spec-row td b,
        .spec-row td strong,
        .notes td b,
        .notes td strong,
        .comment-row td b,
        .comment-row td strong {
            line-height: 1.2 !important;
        }

        /* Cell padding tightening */
        .spec-row td,
        .notes td,
        .comment-row td {
            padding-top: 2px !important;
            padding-bottom: 2px !important;
        }
    </style>

</head>

<body>

    @php
        $currency = $currency->name ?? '';
        $isMH = ($shipping['state'] ?? '') === 'Maharashtra';
        $isGW = ($quotation['quotation_type'] ?? '') === 'GW';
    @endphp

    <div class="paper">

        {{-- ================= HEADER ================= --}}
        <table class="hdr">
            <tr>
                <td style="width:18%; text-align:center; vertical-align:top;">
                    <div style="display:inline-block; text-align:center;">
                        @if(!empty($company['logo']))
                            <img src="{{ $company['logo'] }}" style="display:block; margin:0 auto; height:60px;">
                        @else
                            <strong>QUOTATION</strong>
                        @endif

                        <div style="
                                margin-top:4px;
                                font-size:11px;
                                font-weight:600;
                                color:#333;
                                text-align:center;
                            ">
                            ISO 9001-2015
                        </div>
                    </div>
                </td>


                <td style="vertical-align:top;">
                    <div style="
                    min-height:80px;
                    padding:2px 4px;
                    box-sizing:border-box;
                    text-align:left;
                    font-size: 12px;
                    font-weight: 500;
                    line-height: 1.4;
                    letter-spacing: 1px;
                    width: 100%;
                ">
                        <b class="meta-label">Address :</b>
                        217, 2nd Floor, Champaklal Industrial Estate, Sion East,
                        Mumbai – 400022, India&nbsp;&nbsp;
                        <b class="meta-label">Call :</b> 91-022-43159100
                        <br>

                        <b class="meta-label">Email :</b>
                        sales@chromatographyworld.com,
                        speed@chromatographyworld.com,
                        gm-support@chromatographyworld.com
                        <br>

                        <b class="meta-label alert">GSTN :</b> 27AAGFC1217K1ZM&nbsp;&nbsp;
                        <b class="meta-label alert">UDYAM/MSME no. :</b> UDYAM-MH-19-0078510


                        <b class="meta-label">Web :</b>
                        <span style="color:#0b57d0;">www.chromatographyworld.com</span>
                        <br>

                        <b class="meta-label">Bank :</b> Kotak Mahindra Bank&nbsp;&nbsp;
                        <b class="meta-label">Branch :</b> Matunga&nbsp;&nbsp;
                        <b class="meta-label">IFSC :</b> KKBK0000644&nbsp;&nbsp;
                        <b class="meta-label">A/C :</b> 4611234274
                    </div>
                </td>
            </tr>
        </table>

        {{-- AUTHORISED --}}
        <div class="authorised">
            <b>Authorised For :</b>
            Qualisil, Qaliseal, Gas World, Macherey Nagel,
            G.L.Science (GC Columns), S.A.S.Corporation,
            Nomura Chemicals (Develosil), Sielc (Primesep),
            Sciencix, Poly LC, MZAnalysentechnik, Sepax, Frontier Lab.
            Click <b style="color: #fff2f2;  font-weight: 400;">www.chromatographyworld.com</b> for more details.
        </div>

        <h3 style="text-align:center;color:#800000;font-size:18px;margin:5px;">
            Quotation
        </h3>

        {{-- ================= SHIPPING ================= --}}
        <table class="ship">
            <tr>
                <th>Company</th>
                <td>{{ $shipping['company'] }}</td>
                <th>Contact Person</th>
                <td>{{ $shipping['contact_person'] }}</td>
                <th>GSTN</th>
                <td>{{ $shipping['gstn'] }}</td>
            </tr>

            <tr>
                <th>Address</th>
                <td colspan="5">{{ $shipping['address'] }}</td>
            </tr>

            <tr>
                <th>City</th>
                <td>{{ $shipping['city'] }}</td>
                <th>Pin Code</th>
                <td>{{ $shipping['pincode'] }}</td>
                <th>State</th>
                <td>{{ $shipping['state'] }}</td>
            </tr>

            <tr>
                <th>Country</th>
                <td>{{ $shipping['country'] }}</td>
                <th>Landline</th>
                <td>{{ $shipping['landline'] }}</td>
                <th>Mobile</th>
                <td>{{ $shipping['mobile'] }}</td>
            </tr>

            <tr>
                <th>Email</th>
                <td>{{ $shipping['email'] }}</td>
                <th>Quotation No.</th>
                <td>{{ $quotation['no'] }}</td>
                <th>Quotation Date</th>
                <td>{{ $quotation['date'] }}</td>
            </tr>

            <tr>
                <th>Enq. Ref.</th>
                <td colspan="5">{{ $quotation['ref'] }}</td>
            </tr>
        </table>

        {{-- ================= ITEMS ================= --}}
        <table class="items">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Part No.</th>
                    <th>Description</th>

                    @if($isGW)
                        <th>Maker</th>
                        <th>UOM</th>
                    @endif

                    <th>HSN</th>
                    <th>Qty</th>
                    <th>Unit Price ({{ $currency }})</th>
                    <th>Disc %</th>
                    <th>Net Price ({{ $currency }})</th>

                    @if($isMH)
                        <th>SGST %</th>
                        <th>SGST Amt</th>
                        <th>CGST %</th>
                        <th>CGST Amt</th>
                    @else
                        <th>IGST %</th>
                        <th>IGST Amt</th>
                    @endif

                    <th>Total ({{ $currency }})</th>
                    <th>Delivery</th>
                </tr>
            </thead>

            <tbody>
                @foreach($items as $i => $it)
                    @php
                        $net = (float) $it['net_price'];
                        $igst = (float) $it['igst'];
                        $half = $igst / 2;
                        $igstAmt = $net * $igst / 100;
                        $halfAmt = $net * $half / 100;
                        $rowTotal = $isMH ? $net + $halfAmt + $halfAmt : $net + $igstAmt;
                        $rowClass = ($i % 2 === 0) ? 'odd' : 'even';
                    @endphp


                    <tr class="item-row {{ $rowClass }}">
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $it['part_no'] }}</td>
                        <td>{!! $it['description'] !!}</td>

                        @if($isGW)
                            <td>{{ $it['principal']['type'] ?? '' }}</td>
                            <td>{{ is_array($it['uom'] ?? null) ? ($it['uom']['uom'] ?? '') : ($it['uom'] ?? '') }}</td>
                        @endif

                        <td>{{ $it['hsn_code'] }}</td>
                        <td>{{ $it['quantity'] }}</td>
                        <td>{{ number_format($it['price'], 2) }}</td>
                        <td>{{ $it['discount'] }}</td>
                        <td>{{ number_format($net, 2) }}</td>

                        @if($isMH)
                            <td>{{ number_format($half, 2) }}</td>
                            <td>{{ number_format($halfAmt, 2) }}</td>
                            <td>{{ number_format($half, 2) }}</td>
                            <td>{{ number_format($halfAmt, 2) }}</td>
                        @else
                            <td>{{ number_format($igst, 2) }}</td>
                            <td>{{ number_format($igstAmt, 2) }}</td>
                        @endif

                        <td>{{ number_format($rowTotal, 2) }}</td>
                        <td class="notes">{!! $it['notes'] ?? '' !!}</td>
                    </tr>

                    @if($isGW && !empty($it['specification']))
                        <tr class="spec-row">
                            <td></td>
                            <td colspan="{{ $isMH ? 15 : 13 }}">
                                <b style="color:#4f83b3;">SPECIFICATION :</b>
                                {!! $it['specification'] !!}
                            </td>
                        </tr>
                    @endif

                    @if(!empty($it['heading']) || !empty($it['product_specification']))
                        <tr class="comment-row">
                            <td></td>
                            <td colspan="{{ $isMH ? 15 : 13 }}">
                                @if(!empty($it['heading']))
                                    <b style="color:#6b4f1d;">HEADING :</b>
                                    {!! $it['heading'] !!}<br>
                                @endif
                                @if(!empty($it['product_specification']))
                                    <b style="color:#6b4f1d;">COMMENTS :</b>
                                    {!! $it['product_specification'] !!}
                                @endif
                            </td>
                        </tr>
                    @endif
                @endforeach

                {{-- TOTAL --}}
                <tr class="total-row">
                    <td colspan="{{ $isGW ? 9 : 7 }}" class="label">
                        Grand Total ({{ $currency }})
                    </td>

                    <td>{{ number_format($totals['sub_net_total'], 2) }}</td>

                    @if($isMH)
                        <td></td>
                        <td>{{ number_format(($totals['grand_total'] - $totals['sub_net_total']) / 2, 2) }}</td>
                        <td></td>
                        <td>{{ number_format(($totals['grand_total'] - $totals['sub_net_total']) / 2, 2) }}</td>
                    @else
                        <td></td>
                        <td>{{ number_format($totals['total_igst_total'], 2) }}</td>
                    @endif

                    <td>{{ number_format($totals['grand_total'], 2) }}</td>
                    <td></td>
                </tr>
            </tbody>
        </table>

        {{-- ================= FOOTER ================= --}}
        <table width="100%" cellpadding="0" cellspacing="0" style="
        border-collapse:collapse;
        font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
        font-size:10px;
        margin-top:6px;

        background-image: url('{{ $term_conditon_bg_img }}');
        background-repeat: no-repeat;
        background-position: center;
        background-size: 100% 100%;
    ">

            <!-- TERMS + SIGNATORY -->
            <tr>
                <!-- LEFT : TERMS -->
                <td width="72%" valign="top" style="
                padding:5px 7px;
                line-height:1.25;
                background: transparent;
            ">

                    <div style="
                font-weight:700;
                color:#006c95;
                margin-bottom:3px;
            ">
                        Terms & Conditions:
                    </div>

                    <!-- 🔑 TERMS CONTENT CONTROL -->
                    <div style="
                line-height:1.25;
            ">


                        <div class="terms">
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
            ">
                    <div style="font-weight:700; color:#006c95;">
                        For Chromatography World
                    </div>

                    <div style="font-weight:700; color:#006c95; margin-top:4px;">
                        Authorized signatory
                    </div>

                    <div style="margin-top:6px; font-weight:600;">
                        {{ $prepared_by }}
                    </div>
                </td>
            </tr>

            <!-- FOOT STRIP -->
            <tr>
                <td colspan="2" style="
                background:#c9f1ff;
                text-align:center;
                padding:6px 8px;
                font-size:12px;
                font-weight:500;
            ">
                    <div style="margin-bottom:2px;">
                        {{ $branch_address }}
                    </div>

                    <div style="color:maroon;font-size:14px;font-weight:700;">
                        We Look Forward To Your Valuable Order! &nbsp; Thank You!
                    </div>
                </td>
            </tr>
        </table>



    </div>
    <script type="text/php">
        if (isset($pdf)) {
    
            $pdf->page_script('
                $font = $fontMetrics->get_font("Inter", "bold");
                if (!$font) {
                    $font = $fontMetrics->get_font("DejaVu Sans", "bold");
                }
    
                /* slightly larger & clean */
                $size = 11;
    
                $text = "Page $PAGE_NUM of $PAGE_COUNT";
    
                $width = $fontMetrics->get_text_width($text, $font, $size);
    
                /* bottom-right with safe margin */
                $x = $pdf->get_width() - $width - 18;
                $y = $pdf->get_height() - 18;
    
                $pdf->text($x, $y, $text, $font, $size);
            ');
        }
    </script>

</body>

</html>