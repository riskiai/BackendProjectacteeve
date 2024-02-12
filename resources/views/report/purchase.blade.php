<table>
    <thead>
        <tr>
            <th>DATE</th>
            <th>CONTACT</th>
            <th>PROJECT</th>
            <th>DOC TYPE</th>
            <th>SUB TOTAL</th>
            <th>PPN</th>
            <th>PPH</th>
            <th>TOTAL</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($purchases as $purchase)
            <tr>
                <td>{{ date('d/m/Y', strtotime($purchase->created_at)) }}</td>
                <td>{{ $purchase->company->name }}</td>
                <td>{{ $purchase->project->name }}</td>
                <td>{{ $purchase->doc_type }}</td>
                <td>{{ $purchase->sub_total }}</td>
                {{-- <td>{{ $purchase->taxPpn ? $purchase->sub_total / $purchase->taxPpn->percent : '-' }}</td>
                <td>{{ $purchase->taxPph ? $purchase->sub_total / $purchase->taxPph->percent : '-' }}</td> --}}
                <td>
                    @if ($purchase->taxPpn && is_numeric($purchase->taxPpn->percent))
                        {{ $purchase->sub_total / $purchase->taxPpn->percent }}
                    @else
                        -
                    @endif
                </td>
                
                <td>
                    @if ($purchase->taxPph && is_numeric($purchase->taxPph->percent))
                        {{ $purchase->sub_total / $purchase->taxPph->percent }}
                    @else
                        -
                    @endif
                </td>
                
                {{-- <td>
                    {{ $purchase->taxPpn && $purchase->taxPph ? $purchase->sub_total / $purchase->taxPpn->percent + $purchase->sub_total / $purchase->taxPph->percent : $purchase->total }}
                </td> --}}

                <td>
                    @if ($purchase->taxPpn && is_numeric($purchase->taxPpn->percent) && $purchase->taxPph && is_numeric($purchase->taxPph->percent))
                        {{ ($purchase->sub_total / $purchase->taxPpn->percent) + ($purchase->sub_total / $purchase->taxPph->percent) }}
                    @else
                        {{ $purchase->total }}
                    @endif
                </td>
                
            </tr>
        @endforeach
    </tbody>
</table>
