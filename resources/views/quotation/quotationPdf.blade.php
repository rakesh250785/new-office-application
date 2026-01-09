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
            font-family: "Calibri", "DejaVu Sans", Arial, Helvetica, sans-serif;
            font-size: 11.2px;
            /* content size */
            line-height: 1.5;
            font-weight: 400;
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
            margin: 15mm;
        }

        .paper {
            box-sizing: border-box;
            padding: 6mm;
        }

        /* ================= TABLE CORE ================= */
        table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
            table-layout: auto;
        }

        /* =================================================
           UNIFORM BORDER SYSTEM (PDF SAFE)
           ONE-DIRECTION DRAWING ONLY
           ================================================= */

        /* Reset all borders */
        th,
        td {
            border: none;
            padding: 2px;
            vertical-align: top;
            white-space: normal;
            word-break: break-word;
            overflow-wrap: anywhere;
            box-sizing: border-box;
            font-family: "Calibri", "DejaVu Sans", Arial, Helvetica, sans-serif;

            /* draw ONLY from right + bottom */
            border: 0.4px solid #c8c8c8;
        }

        /* Close LEFT edge */
        table tr th:first-child,
        table tr td:first-child {
            border-left: 0.4px solid #c8c8c8;
        }

        /* Close TOP edge */
        table thead tr:first-child th,
        table tbody tr:first-child td {
            border-top: 0.4px solid #c8c8c8;
        }

        tr {
            page-break-inside: avoid;
        }

        /* ================= HEADER (OLD-STYLE FLUSH) ================= */
        .hdr {
            background: #bcecfa;
            width: 100%;
            margin: 0 !important;
            padding: 0 !important;
            border: none !important;
            border-collapse: collapse;
            font-family: "Calibri", "DejaVu Sans", Arial, Helvetica, sans-serif;
        }

        /* Remove all borders */
        .hdr,
        .hdr tr,
        .hdr td {
            border: none !important;
            font-family: "Calibri", "DejaVu Sans", Arial, Helvetica, sans-serif;
        }

        /* Force table row to hug content */
        .hdr tr {
            height: auto;
        }

        /* Cells */
        .hdr td {
            /* padding: 3px 8px 2px 8px; */
            /*  tiny bottom padding like old */
            vertical-align: top;
            font-weight: 500;
            line-height: 1.2;
            /*  tighter like old */
            color: #333;
        }

        /* Logo — critical */
        .hdr img {
            display: block;
            /* removes image baseline gap */
            height: 60px;
            margin: 0;
            padding: 0;
        }

        /* ISO text */
        .hdr .iso,
        .hdr-iso {
            font-size: 11px;
            font-weight: 600;
            line-height: 1.2;
            margin-top: 2px;
        }

        /* Kill hidden spacing from inner elements */
        .hdr p,
        .hdr div,
        .hdr span {
            margin: 0;
            padding: 0;
            line-height: 1.2;
        }

        /* No gap below header */
        .hdr+* {
            margin-top: 0 !important;
        }


        /* ================= META ================= */
        .meta-label {
            color: #006c95;
            font-weight: 600;
            letter-spacing: 0.2px;
            font-family: "Calibri", "DejaVu Sans", Arial, Helvetica, sans-serif;
        }

        .meta-label.alert {
            color: #b30f16;
            font-family: "Calibri", "DejaVu Sans", Arial, Helvetica, sans-serif;
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
            line-height:1.35;
            letter-spacing: 0.25px;
            padding: 2px;
            font-family: "Calibri", "DejaVu Sans", Arial, Helvetica, sans-serif;
        }

        .authorised td {
            border: none !important;
        }

        .authorised b {
            font-weight: 600;
        }

        /* ================= SHIPPING TABLE — CLEAN GRID ================= */
        .ship {
            background: #f2f8f9;
            margin-bottom: 7px;
            width: 100%;
            border-collapse: collapse;
            table-layout: auto;
            font-family: "Calibri", "DejaVu Sans", Arial, Helvetica, sans-serif;
        }

        /* Reset inherited borders */
        .ship th,
        .ship td {
            padding: 0px 4px;
            vertical-align: middle;
            word-break: break-word;
            text-align: left;
            font-size: 11px;
            font-family: "Calibri", "DejaVu Sans", Arial, Helvetica, sans-serif;
        }

        /* Draw ONLY horizontal row lines */
        .ship tr {
            border: 0.2px solid #cfcfcf;
        }

        /* Top border */
        .ship tr:first-child th,
        .ship tr:first-child td {
            border: 0.2px solid #cfcfcf;
        }

        /* Vertical separators — drawn once */
        .ship th:not(:last-child),
        .ship td:not(:last-child) {
            border: 0.2px solid #cfcfcf;
        }

        /* Left & right edges */
        .ship th:first-child,
        .ship td:first-child {
            border: 0.2px solid #cfcfcf;
        }

        .ship th:last-child,
        .ship td:last-child {
            border: 0.2px solid #cfcfcf;
        }

        /* Header cells */
        .ship th {
            color: #006c95;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
            font-family: "Calibri", "DejaVu Sans", Arial, Helvetica, sans-serif;
        }

        /* Value cells */
        .ship td {
            color: #222;
            font-weight: 240;
            padding: 0px 4px;
            vertical-align: middle;
            word-break: break-word;
            text-align: left;
            font-family: "Calibri", "DejaVu Sans", Arial, Helvetica, sans-serif;
        }

        /* ================= ITEMS ================= */
        .items {
            margin-bottom: 7px;
            font-family: "Calibri", "DejaVu Sans", Arial, Helvetica, sans-serif;
        }   

        .items th {
            background: #eef6f9;
            color: #004f6e;
            font-weight: 600;
            text-align: left;
            font-family: "Calibri", "DejaVu Sans", Arial, Helvetica, sans-serif;
            font-size: 13px;
        }

        /* Row colors */
        .items tbody tr td,
        th {
            background: #eef6f9;
            font-size: 11px;
            font-family: "Calibri", "DejaVu Sans", Arial, Helvetica, sans-serif;
        }

        /* ODD rows */
        .items tbody tr.item-row.odd td,
        .items tbody tr.spec-row.odd td,
        .items tbody tr.comment-row.odd td {
            background: #fdecec;
        }

        /* EVEN rows */
        .items tbody tr.item-row.even td,
        .items tbody tr.spec-row.even td,
        .items tbody tr.comment-row.even td {
            background: #f6d6d6;
        }

        /* ================= TOTAL ROW ================= */
        .items tbody tr.total-row td {
            background: #333 !important;
            color: #f5f2e8;
            font-size: 15px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }

        /* Remove all inner borders first */
        .items tbody tr.total-row td {
            border: none !important;
        }

        /* Left edge */
        .items tbody tr.total-row td:first-child {
            border-left: 0.5px solid #cfcfcf !important;
        }

        /* Right edge */
        .items tbody tr.total-row td:last-child {
            border-right: 0.5px solid #cfcfcf !important;
        }

        /* Bottom edge (entire row) */
        .items tbody tr.total-row td {
            border-bottom: 0.5px solid #cfcfcf !important;
        }

        .total-row .label {
            text-align: center;
        }

        /* ================= FOOTER / TERMS ================= */
        .footer-title {
            color: #006c95;
            font-weight: 700;
            font-size: 13.2px;
            letter-spacing: 0.2px;
            font-family: "Calibri", "DejaVu Sans", Arial, Helvetica, sans-serif;
        }

        .html-content ul,
        .html-content ol {
            padding-left: 18px;
            margin: 6px 0;
            font-family: "Calibri", "DejaVu Sans", Arial, Helvetica, sans-serif;
        }

        .html-content li {
            margin-bottom: 6px;
            font-family: "Calibri", "DejaVu Sans", Arial, Helvetica, sans-serif;
        }

        .terms,
        .terms * {
            font-size: 12px;
            line-height: 1.35 !important;
            color: #2b2b2b;
            font-weight: 600;
            font-family: "Calibri", "DejaVu Sans", Arial, Helvetica, sans-serif;
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

        /* ================= HTML EDITOR HARD RESET ================= */
        .spec-row td,
        .comment-row td,
        .notes td,
        .items td.description,
        .items td.heading,
        .items td.html-content {
            line-height: 1.2 !important;
            font-family: "Calibri", "DejaVu Sans", Arial, Helvetica, sans-serif;
        }

        .spec-row td p,
        .comment-row td p,
        .notes td p,
        .items td.description p,
        .items td.heading p,
        .items td.html-content p {
            margin: 0 !important;
            padding: 0 !important;
            font-family: "Calibri", "DejaVu Sans", Arial, Helvetica, sans-serif;
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
            font-family: "Calibri", "DejaVu Sans", Arial, Helvetica, sans-serif;
        }

        .spec-row td ul,
        .spec-row td ol,
        .notes td ul,
        .notes td ol,
        .comment-row td ul,
        .comment-row td ol {
            margin: 0 !important;
            padding-left: 14px !important;
            font-family: "Calibri", "DejaVu Sans", Arial, Helvetica, sans-serif;
        }

        .spec-row td li,
        .notes td li,
        .comment-row td li {
            margin: 0 !important;
            padding: 0 !important;
            line-height: 1 !important;
            font-family: "Calibri", "DejaVu Sans", Arial, Helvetica, sans-serif;
        }

        .spec-row td,
        .notes td,
        .comment-row td {
            padding-top: 2px !important;
            padding-bottom: 2px !important;
            font-family: "Calibri", "DejaVu Sans", Arial, Helvetica, sans-serif;
        }

        .items thead {
            display: table-row-group;
            font-family: "Calibri", "DejaVu Sans", Arial, Helvetica, sans-serif;
        }

        /* ===== MONEY COLUMNS — NEVER WRAP ===== */
        .items th.money,
        .items td.money,
        .items .total-row td {
            white-space: nowrap !important;
            word-break: keep-all !important;
            overflow-wrap: normal !important;
            text-align: left;
            padding: 2px !important;
            font-variant-numeric: tabular-nums;
            font-family: "Calibri", "DejaVu Sans", Arial, Helvetica, sans-serif;
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
    font-family: Calibri, 'DejaVu Sans', Arial, Helvetica, sans-serif;
    
">
            <tr>
                <!-- LEFT : LOGO + ISO -->
                <td style="
            width:18%;
            text-align:center;
            vertical-align:top;
            padding:6px 4px;
            font-family: Calibri, 'DejaVu Sans', Arial, Helvetica, sans-serif;
              
        ">
                    @if(!empty($company['logo']))
                        <img src="{{ $company['logo'] }}"
                            style="max-width:200px; height:auto; display:block; margin:0 auto;margin-top: -12px">
                    @else
                        <div style="font-weight:700; font-size:14px;">QUOTATION</div>
                    @endif

                    <div style="
                        margin-top: -5px;
                        font-size:12px;
                        font-weight:600;
                        color:#111;
                        font-family: Calibri, 'DejaVu Sans', Arial, Helvetica, sans-serif;
                    ">
                        ISO 9001-2015
                    </div>
                </td>

                <!-- RIGHT : DETAILS -->
                <td
                style="
                  vertical-align: top;
                  padding: 6px 8px;
                  font-family: Calibri, 'DejaVu Sans', Arial, Helvetica, sans-serif;
                  font-size: 12px;          /* content size */
                 line-height: 1.30;
                  color: #111;
                "
              >
                <!-- 1️⃣ FIRST LINE : ADDRESS -->
                <b style="font-size:11px;color:#006c95;">Address :</b>
                &nbsp;&nbsp;&nbsp;&nbsp; 217, 2nd Floor, Champaklal Industrial Estate, Sion East,
                Mumbai – 400022, India  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <b style="font-size:11px;color:#006c95;">Call :</b>
                &nbsp;&nbsp; 91-022-43159100
                <br>
              
                <!-- 2️⃣ SECOND LINE : EMAILS + CALL -->
                <b style="font-size:11px;color:#006c95;">Email :</b>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; sales@chromatographyworld.com,&nbsp;&nbsp;&nbsp;&nbsp;
                speed@chromatographyworld.com,&nbsp;&nbsp;&nbsp;&nbsp;
                gm-support@chromatographyworld.com
                &nbsp;&nbsp;
               
                <br>
              
                <!-- 3️⃣ THIRD LINE : GST + MSME + WEBSITE -->
                <b style="font-size:11px;color:#800000;">GSTN :</b>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 27AAGFC1217K1ZM
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <b style="font-size:11px;color:#800000;">Udyam / MSME :</b>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; UDYAM-MH-19-0078510
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <b style="font-size:11px;color:#006c95;">Web :</b>
                <span style="color:#0b57d0;">&nbsp;&nbsp;&nbsp;&nbsp; www.chromatographyworld.com</span>
                <br>
              
                <!-- 4️⃣ FOURTH LINE : BANK DETAILS -->
                <b style="font-size:11px;color:#006c95;">Bank :</b>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Kotak Mahindra Bank
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <b style="font-size:11px;color:#006c95;">Branch :</b>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Matunga
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <b style="font-size:11px;color:#006c95;">IFSC :</b>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; KKBK0000644
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <b style="font-size:11px;color:#006c95;">A/C :</b>
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
            <b
              style="
                font-family: Calibri, 'DejaVu Sans', Arial, Helvetica, sans-serif;
                color: #fff2cc;
                text-decoration: underline;
                font-size: 14px;
              "
            >
              www.chromatographyworld.com
            </b>
            for more details.
          </div>
          

        <h4 style="
        text-align:center;
        color:#800000;
        font-size:18px;
        font-weight:600;
        margin:2px 0;
        vertical-align: middle;
        letter-spacing:0.3px;
        font-family: Calibri, 'DejaVu Sans', Arial, Helvetica, sans-serif;
    ">
            Quotation
        </h4>


        {{-- ================= SHIPPING ================= --}}
        <table class="ship">
            <tr>
                <th>Company</th>
                <td colSpan="6">{{ $shipping['company'] }}</td>
                <th>GSTN</th>
                <td  colSpan="3">{{ $shipping['gstn'] }}</td>
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
                <td colSpan="3">{{ $shipping['contact_person'] }}</td>
                <th>Email</th>
                <td colSpan="2">{{ $shipping['email'] }}</td>
                <th>Landline</th>
                <td>{{ $shipping['landline'] }}</td>
                <th>Mobile</th>
                <td>{{ $shipping['mobile'] }}</td>
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
            <thead>
                <tr>
                    <th>Sr.</th>
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

                    @if($isGW && !empty($it['specification']))
                        <tr class="spec-row {{ $rowClass }}">
                            <td colspan="{{ $noteColspan}}">
                                <b style="color:#6b4f1d; font-size: 8px;">SPECIFICATION :</b>
                                {!! $it['specification'] !!}
                            </td>
                        </tr>
                    @endif

                    @if(!empty($it['heading']) || !empty($it['product_specification']))
                        <tr class="comment-row {{ $rowClass }}">
                            <td colspan="{{ $noteColspan }}" style="padding:4px 6px;">

                                <table width="100%" cellpadding="0" cellspacing="0" style="
                                                                                                                            border:0;
                                                                                                                            border-collapse:collapse;
                                                                                                                        ">
                                    <tr>
                                        {{-- LEFT : HEADING (60%) --}}
                                        <td width="60%" valign="top"
                                            style="
                                                                                                                                    border:0;
                                                                                                                                    padding-right:8px;
                                                                                                                                    font-size:8.5px;
                                                                                                                                    line-height:1.35;
                                                                                                                                    word-break:break-word;
                                                                                                                                      font-family: Calibri, 'DejaVu Sans', Arial, Helvetica, sans-serif;
                                                                                                                                ">
                                            @if(!empty($it['heading']))
                                                <b style="color:#6b4f1d;">HEADING:</b><br>
                                                {!! $it['heading'] !!}
                                            @endif
                                        </td>

                                        {{-- RIGHT : COMMENTS (40%) --}}
                                        <td width="40%" valign="top"
                                            style="
                                                                                                                                    border:0;
                                                                                                                                    font-size:8.5px;
                                                                                                                                    line-height:1.35;
                                                                                                                                    word-break:break-word;
                                                                                                                                      font-family: Calibri, 'DejaVu Sans', Arial, Helvetica, sans-serif;
                                                                                                                                ">
                                            @if(!empty($it['product_specification']))
                                                <b style="color:#6b4f1d;">COMMENTS:</b><br>
                                                {!! $it['product_specification'] !!}
                                            @endif
                                        </td>
                                    </tr>
                                </table>

                            </td>
                        </tr>
                    @endif


                @endforeach

                {{-- TOTAL --}}
                <tr class="total-row">
                    <td colspan="{{ $isGW ? 9 : 7 }}" class="label money">
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
                    <tr>
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

            font-family: Calibri, DejaVu Sans, Arial, Helvetica, sans-serif;
            font-size:10px;
            margin-top:6px;

            background-image: url('{{ $term_conditon_bg_img }}');
            background-repeat: no-repeat;
            background-position: center;
            background-size: 100% 100%;
              font-family: Calibri, 'DejaVu Sans', Arial, Helvetica, sans-serif;
        ">

            <!-- TERMS + SIGNATORY -->
            <tr style="border:0;">
                <!-- LEFT : TERMS -->
                <td width="72%" valign="top" style="
            padding:5px 7px;
            line-height:1.25;
            background: transparent;
            border:0;
              font-family: Calibri, 'DejaVu Sans', Arial, Helvetica, sans-serif;
        ">

                    <div style="
                font-weight:700;
                color:#006c95;
                margin-bottom:3px;
                  font-family: Calibri, 'DejaVu Sans', Arial, Helvetica, sans-serif;
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
              font-family: Calibri, 'DejaVu Sans', Arial, Helvetica, sans-serif;
        ">
                    <div style="font-weight:700; color:#006c95;">
                        For Chromatography World
                    </div>

                    <div style="font-weight:700; color:#006c95; margin-top:4px;">
                        Authorized Signatory
                    </div>

                    <div style="margin-top:6px; font-weight:600;">
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
    
        font-family: Calibri, DejaVu Sans, Arial, Helvetica, sans-serif;
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
                font-weight:500;
                border:0;
            ">
                    <div style="margin-bottom:2px;">
                        {{ $branch_address }}
                    </div>

                    <div style="
                    color:maroon;
                    font-size:13px;
                    font-weight:700;
                      font-family: Calibri, 'DejaVu Sans', Arial, Helvetica, sans-serif;
                ">
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