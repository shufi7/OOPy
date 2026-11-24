<table class="certification-grade-table" aria-label="Komponen penilaian">
    <thead>
        <tr><th class="position" scope="col" style="width: 8%">No.</th><th scope="col">Komponen Penilaian</th><th class="score" scope="col" style="width: 13%">Nilai</th><th scope="col" style="width: 23%">Status</th></tr>
    </thead>
    <tbody>
        @foreach ($transcript['rows'] as $row)
            <tr>
                <td class="position">{{ $loop->iteration }}</td>
                <th scope="row">{{ $row['label'] }}</th>
                <td class="score">{{ \App\Services\CertificateTranscriptService::score($row['score']) }}</td>
                <td>{{ $row['status'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
