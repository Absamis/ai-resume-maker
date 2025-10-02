<div style="font-family: Arial, sans-serif;">

    <!-- Header Images -->
    <p style="text-align:right;">
        <img src="https://absamtech.online/ai-head-img.png" width="75" />
    </p>
    <p>
        <img src="https://absamtech.online/ai-bg.png" width="100%" />
    </p>

    <!-- Name -->
    <p style="font-size:28px; color:#5a677d; text-transform:uppercase;">
        {{$data["lastname"]}} {{$data["firstname"]}}
    </p>

    <!-- Page Break -->
    <p style="page-break-before: always;"></p>

    <!-- Info Table -->
    <table style="width:100%; border:0;">
        <tr>
            <td><b>ADRESSE:</b> {{$data["address"] ?? ''}}</td>
            <td><b>GEBURTSTAG:</b> {{$data["date_of_birth"] ?? ''}}</td>
        </tr>
        <tr>
            <td><b>WOHNORT:</b> {{$data["place_of_residence"] ?? ''}}</td>
            <td><b>GEBURTSORT:</b> {{$data["birth_place"] ?? ''}}</td>
        </tr>
        <tr>
            <td><b>TELEFON:</b> {{$data["phone_number"] ?? ''}}</td>
            <td><b>NATIONALITÄT:</b> {{$data["nationality"] ?? ''}}</td>
        </tr>
        <tr>
            <td><b>E-MAIL:</b> {{$data["email"] ?? ''}}</td>
            <td><b>FAMILIENSTAND:</b> {{$data["martial_status"] ?? ''}}</td>
        </tr>
    </table>

</div>
