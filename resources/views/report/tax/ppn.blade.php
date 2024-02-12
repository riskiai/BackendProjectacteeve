<table>
    <thead>
        <tr>
            <th>DATE</th>
            <th>CONTACT</th>
            <th>PROJECT</th>
            <th>DPP</th>
            <th>PPN</th>
            <th>ATTACHMENT</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($purchases as $purchase)
            <tr>
                <td>{{ date('d/m/Y', strtotime($purchase->created_at)) }}</td>
                <td>{{ $purchase->company->name }}</td>
                <td>{{ $purchase->project->name }}</td>
                <td>{{ $purchase->sub_total }}</td>
                {{-- <td>{{ $purchase->taxPpn ? $purchase->sub_total / $purchase->taxPpn->percent : '-' }}</td> --}}
                <td>
                    @if ($purchase->taxPpn && is_numeric($purchase->taxPpn->percent))
                        {{ $purchase->sub_total / $purchase->taxPpn->percent }}
                    @else
                        -
                    @endif
                </td>
                
                <td>
                    <a href="{{ asset("storage/$purchase->file") }}">
                        {{ "$purchase->doc_type/$purchase->doc_no/" . date('Y', strtotime($purchase->created_at)) . '.pdf' }}
                    </a>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
