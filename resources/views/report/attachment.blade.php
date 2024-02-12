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
            <tr>
                <td>{{ date('d/m/Y', strtotime($purchase->created_at)) }}</td>
                <td>{{ $purchase->company->name }}</td>
                <td>{{ $purchase->project->name }}</td>
                <td>
                    <a href="{{ asset("storage/$purchase->file") }}">
                        {{ "$purchase->doc_type/$purchase->doc_no/" . date('Y', strtotime($purchase->created_at)) . '.pdf' }}
                    </a>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>