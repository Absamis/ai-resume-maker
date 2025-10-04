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
    <div>
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

    {{-- SECOND LAYER --}}
    <div>
        <p style="text-align: center; font-size: 24px; color: #5a677d; text-transform: uppercase;">
            {{$data["lastname"]." ". $data["firstname"]}}
        </p>

        <p style="text-align: center; color: #a5a5a3;">
            <i>{{ $data["short_bio"] ?? '' }}</i>
        </p>

        <p style="text-align: center; color: #5a677d;"><b>EXPERTISE</b></p>

        <ul>
            @foreach ($data["expertises"] as $exp)
                <li>— {{$exp ?? ''}}</li>
            @endforeach
        </ul>
    </div>

    {{-- THIRD LAYER  --}}
    <div>
        <p style="text-align: center; color: #5a677d;"><b>BERUFLICHER WERDEGANG</b></p>

        <ul>
            @foreach ($data["professional_experience"] as $exp)
                <li>
                    {{ ($exp["start_date"] ?? '') }} - {{ ($exp["end_date"] ?? '') }}  
                    <b>{{ $exp["position"] ?? '' }}</b> - {{ $exp["company"] ?? '' }}, {{ $exp["company_location"] ?? '' }}
                </li>
            @endforeach
        </ul>

        <p style="text-align: center; color: #5a677d;"><b>SKILL SET</b></p>

        <ul>
            @foreach (($data["skills"] ?? []) as $sk)
                <li>{{ $sk }}</li>
            @endforeach
        </ul>
    </div>

    {{-- FORTH LAYER  --}}
    <div>
        <div>
            <p style="text-transform: uppercase; font-size: 24px; color: #5a677d;">
                {{$data["lastname"]. " ". $data["firstname"]}}
            </p>
            <p>
                {{$data["short_bio"] ?? ''}}
            </p>
        </div>

        <div>
            <table style="width: 100%;">
                <tr>
                    <td>{{$data["phone_number"] ?? ''}}</td>
                    <td>{{$data["email"] ?? ''}}</td>
                    <td>{{$data["address"] ?? ''}}</td>
                    <td>
                        @if ($data["linkedin_link"])
                            Linkedin: {{$data["linkedin_link"] ?? ''}}
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        <div>
            <p><b>{{ $data["job_application"]["company_name"] ?? '' }}</b></p>
            <p><b>z.Hd. {{ $data["job_application"]["employer_name"] ?? '' }}</b></p>
            <p>{{ $data["job_application"]["company_location"] ?? '' }}</p>
            <p>{{ $data["job_application"]["company_zipcode"] ?? '' }}</p>
        </div>

        <p style="text-align:right;">
            {{ $data["job_application"]["date"] }}
        </p>

        <div>
            <p style="font-size: 18px; color: #5a677d;">
                BEWERBUNG ALS {{ $data["job_application"]["job_title"] ?? '' }}
            </p>
            <div>
                {{$data["job_application"]["cover_letter"] ?? ''}}
            </div>
            
            <p>
                Mit freundlichen Grüßen
                {{$data["firstname"]. " ". $data["lastname"]}}
            </p>
        </div>
    </div>

    {{-- FITH LAYER  --}}
    <div>
        <div style="padding: 0px 20px;">
            <p style="text-transform: uppercase; font-size: 40px; margin-top: 3em; color: #5a677d;">
                {{$data["lastname"]}}<br/>{{$data["firstname"]}}
            </p>
            <p style="font-size:18px;">{{$data["short_bio"] ?? ''}}</p>
        </div>

        <div style="padding: 0px 20px;">
            <table style="width: 100%; border-top: 2px solid #666; border-bottom: 1px solid #666;">
                <tr>
                    <td style="padding: 10px; text-align: left;">
                        {{$data["phone_number"] ?? ''}}
                    </td>
                    <td style="padding: 10px; text-align: left;">
                        ✉ {{$data["email"] ?? ''}}
                    </td>
                    <td style="padding: 10px; text-align: left;">
                        {{$data["address"] ?? ''}}
                    </td>
                    <td style="padding: 10px; text-align: left;">
                        @if ($data["linkedin_link"])
                            <a href="{{$data["linkedin_link"] ?? ''}}">LinkedIn</a>
                        @endif
                    </td>
                </tr>
            </table>
        </div>
    </div>

    {{-- SIXTH LAYER  --}}
    <div>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <!-- LEFT COLUMN -->
                <td style="width: 35%; padding: 15px; border-right: 1px solid #666; vertical-align: top;">
                    <p style="font-size: 20px; color: #5a677d;"><b>PERSÖNLICHES</b></p>
                    <p><b>Geburtsdatum:</b> {{$data["date_of_birth"] ?? ''}}</p>
                    <p><b>Geburtsort:</b> {{$data["place_of_birth"] ?? ''}}</p>
                    <p><b>Nationalität:</b> {{$data["nationality"] ?? ''}}</p>
                    <p><b>Familienstand:</b> {{$data["martial_status"] ?? ''}}</p>

                    <p style="font-size: 20px; color: #5a677d;"><b>EXPERTISE</b></p>
                    <ul>
                        @foreach (($data["expertises"] ?? []) as $ex)
                            <li>{{ $ex }}</li>
                        @endforeach
                    </ul>

                    <p><b>Soft Skills</b></p>
                    <ul>
                        @foreach (($data["soft_skills"] ?? []) as $ex)
                            <li>{{ $ex }}</li>
                        @endforeach
                    </ul>

                    <p><b>Fachliche Kompetenzen</b></p>
                    <ul>
                        @foreach (($data["professional_skills"] ?? []) as $ex)
                            <li>{{ $ex }}</li>
                        @endforeach
                    </ul>

                    <p style="font-size: 20px; color: #5a677d;"><b>SPRACHEN</b></p>
                    @foreach (($data["languages"] ?? []) as $ex)
                        <p><b>{{$ex['language']}}:</b> {{$ex['proficiency_level']}}</p>
                    @endforeach

                    <p style="font-size: 20px; color: #5a677d;"><b>ZERTIFIKATE</b></p>
                    <ul>
                        @foreach (($data["certifications"] ?? []) as $ex)
                            <li>{{ $ex }}</li>
                        @endforeach
                    </ul>

                    <p style="font-size: 20px; color: #5a677d;"><b>STUDIUM</b></p>
                    @foreach (($data["education"] ?? []) as $ex)
                        <p><b>{{$ex['start_date']}} - {{$ex['end_date']}}</b></p>
                        <p style="color:#5a677d;">{{ $ex['degree_held'] }}</p>
                        <ul>
                            @foreach (($ex["courses_studied"] ?? []) as $csex)
                                <li>{{$csex}}</li>
                            @endforeach
                        </ul>
                    @endforeach

                    <p style="font-size: 20px; color: #5a677d;"><b>INTERESSEN</b></p>
                    <ul>
                        @foreach (($data["interests"] ?? []) as $ex)
                            <li>{{$ex}}</li>
                        @endforeach
                    </ul>

                    <p style="font-size: 20px; color: #5a677d;"><b>SONSTIGES</b></p>
                    <ul>
                        @foreach (($data["other_hobbies"] ?? []) as $ex)
                            <li>{{$ex}}</li>
                        @endforeach
                    </ul>
                </td>

                <!-- RIGHT COLUMN -->
                <td style="width: 65%; padding: 15px; vertical-align: top;">
                    <p style="font-size: 20px; color: #5a677d;"><b>ÜBER MICH</b></p>
                    <p>{{ $data["professional_summary"] ?? '' }}</p>

                    <p style="font-size: 20px; color: #5a677d;"><b>BERUFSERFAHRUNG</b></p>
                    @foreach ($data["professional_experience"] as $exp)
                        <p><b>{{ $exp["start_date"] }} - {{ $exp["end_date"] }}</b></p>
                        <p style="font-size: 18px; color: #5a677d;">{{ $exp["job_title"] }}</p>
                        <p><i>{{ $exp["company"] }}, {{ $exp["company_location"] }}</i></p>
                        <ul>
                            @foreach (($exp["achievements"] ?? []) as $ex)
                                <li>{{ $ex }}</li>
                            @endforeach
                        </ul>
                    @endforeach
                </td>
            </tr>
        </table>
    </div>

</div>
