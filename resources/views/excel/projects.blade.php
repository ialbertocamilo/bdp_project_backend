<table>
    <thead style="background-color: blue">
        <tr>
            <th>CÓDIGO</th>
            <th>DESCRIPCIÓN</th>
            <th>TIPO</th>
            <th>FASE</th>
            <th>ORIGEN</th>
            <th>FECHA REGISTRO</th>
            <th>DURACIÓN</th>
            <th>PRESUPUESTO USD</th>
        </tr>
    </thead>
    <tbody>
    @php
    $i = 1;
    @endphp
    @foreach($projects as $project)
        <tr>
            <td>{{ $i++ }}</td>
            <td>{{ $project['DESCRIPCION'] }}</td>
            <td>{{ $project['TIPO'] }}</td>
            <td>{{ $project['FASE'] }}</td>
            <td>{{ $project['ORIGEN'] }}</td>
            <td>{{ $project['FECHA_REGISTRO'] }}</td>
            <td>{{ $project['DURACION'] }}</td>
            <td>{{ $project['PRESUPUESTO_USD'] }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
