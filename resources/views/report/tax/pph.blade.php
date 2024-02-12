<table>
    <thead>
        <tr>
            <th>DATE</th>
            <th>CONTACT</th>
            <th>PROJECT</th>
            <th>DPP</th>
            <th>PPH TYPE</th>
            <th>PPH RATE %</th>
            <th>PPH</th>
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
                <td>{{ $purchase->taxPph->name }}</td>
                <td>{{ $purchase->taxPph->percent }}</td>
                {{-- <td>{{ $purchase->taxPph ? $purchase->sub_total / $purchase->taxPph->percent : '-' }}</td> --}}
                <td>
                    @if ($purchase->taxPph && is_numeric($purchase->taxPph->percent))
                        {{ $purchase->sub_total / $purchase->taxPph->percent }}
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
