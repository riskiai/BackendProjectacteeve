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
                    {{ $purchase->total }}
                </td>

                <td>
                    @foreach ($purchase->documents as $key => $document)
                        <a href="{{ asset("storage/$document->file_path") }}">
                            {{ "$purchase->doc_type/$purchase->doc_no.$document->id/" . date('Y', strtotime($purchase->created_at)) . '.pdf' }}
                        </a>
                    @endforeach
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
