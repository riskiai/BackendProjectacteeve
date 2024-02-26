<table>
    <thead>
        <tr>
            <th>DATE</th>
            <th>CONTACT</th>
            <th>PROJECT</th>
            <th>ATTACHMENT</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($purchases as $purchase)
            @foreach ($purchase->documents as $key => $document)
                <tr>
                    <td>{{ date('d/m/Y', strtotime($purchase->created_at)) }}</td>
                    <td>{{ $purchase->company->name }}</td>
                    <td>{{ $purchase->project->name }}</td>
                    <td>
                        <a href="{{ asset("storage/$document->file_path") }}">
                            {{ "$purchase->doc_type/$purchase->doc_no.$document->id/" . date('Y', strtotime($purchase->created_at)) . '.pdf' }}
                        </a>
                    </td>
                </tr>
            @endforeach
        @endforeach
    </tbody>
</table>
