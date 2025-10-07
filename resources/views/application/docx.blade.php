<!DOCTYPE html>

<html lang="en">

<head>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
         .page-break {
            page-break-after: always; /* forces content after to start on new page */
        }
        .icon-box{
            display:flex;
            margin: 0;
            align-items:center;
        }
        .icon-box .icon{
            margin-right: 7px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table th, table td {
            padding: 8px;
        }
        /* ✅ Prevent splitting rows */
        tr {
            page-break-inside: avoid;
        }
        thead {
            display: table-header-group;
        }
        tfoot {
            display: table-footer-group;
        }
    </style>
</head>

<body style="font-family: 'Montserrat', sans-serif; margin: 0px; padding: 0px;">

    <img src="https://absamtech.online/ai-head-img.png" style="top:0;right:0;z-index: 1;position: absolute; width: 75px;" />
    <img style="width: 100%;" src="https://absamtech.online/ai-bg.png" />
    <div style="padding: 0px 20px; margin-top: 100px;">
        <p style="text-transform: uppercase; font-size: 30px; font-size: 40px; margin:3em 20px; color: #5a677d;">
            {{$data["lastname"]." ". $data["firstname"]}}</p>
    </div>
    <div class="page-break"></div>
    <div style="padding: 20px; ">
        <table style="width: 100%; margin-top: 25px;">
            <tr style="padding: 15px;">
                <td style="padding: 10px; color: #a5a5a3;">
                    <span>
                    <svg style="opacity:0.3;" width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M1.58594 10.5453L10.9393 1.43968C11.0786 1.30029 11.244 1.18971 11.426 1.11427C11.6081 1.03883 11.8032 1 12.0003 1C12.1973 1 12.3925 1.03883 12.5745 1.11427C12.7566 1.18971 12.922 1.30029 13.0613 1.43968L22.4146 10.5453" stroke="#71717A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path> <path d="M16.5 4.62866V3.12866H20V7.84375" stroke="#71717A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path> <path d="M3 9.5V18.069V21.6552C3 22.0118 3.1724 22.3539 3.47928 22.6061C3.78616 22.8583 4.20237 23 4.63636 23H19.3636C19.7976 23 20.2138 22.8583 20.5207 22.6061C20.8276 22.3539 21 22.0118 21 21.6552V9.5" stroke="#71717A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path> <path d="M15 23V18C15 16.3431 13.6569 15 12 15V15C10.3431 15 9 16.3431 9 18V23" stroke="#71717A" stroke-width="1.5"></path> <path d="M9 10.25C8.58579 10.25 8.25 10.5858 8.25 11C8.25 11.4142 8.58579 11.75 9 11.75V10.25ZM15 11.75C15.4142 11.75 15.75 11.4142 15.75 11C15.75 10.5858 15.4142 10.25 15 10.25V11.75ZM9 11.75H15V10.25H9V11.75Z" fill="#71717A"></path> <path d="M9 7.25C8.58579 7.25 8.25 7.58579 8.25 8C8.25 8.41421 8.58579 8.75 9 8.75V7.25ZM15 8.75C15.4142 8.75 15.75 8.41421 15.75 8C15.75 7.58579 15.4142 7.25 15 7.25V8.75ZM9 8.75H15V7.25H9V8.75Z" fill="#71717A"></path> </g></svg>
                    </span>
                    <b>ADRESSE</b><span
                        style="margin-left: 27px;">{{$data["address"] ?? ''}}</span></td>
                <td style="padding: 10px; color: #a5a5a3;">
                    
                    <b>GEBURTSTAG</b><span
                        style="margin-left: 38px;">{{$data["date_of_birth"] ?? ''}}</span>
                    <span>
                        <svg style="opacity:0.3;" version="1.1" id="Uploaded to svgrepo.com" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="20px" height="20px" viewBox="0 0 32 32" xml:space="preserve" fill="none"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <style type="text/css"> .feather_een{fill:#0B1719;} </style> <path class="feather_een" d="M28,9.5C28,3.552,25.196,0,20.5,0c-3.114,0-5.377,1.581-6.561,4.368C13.197,4.125,12.382,4,11.5,4 C6.804,4,4,7.552,4,13.5c0,4.363,2.949,7.496,6.033,8.299l-0.431,0.646c-0.205,0.308-0.224,0.701-0.049,1.026 C9.727,23.798,10.065,24,10.434,24h0.505c-0.124,0.527-0.439,0.856-0.824,1.239C9.593,25.762,9,26.354,9,27.5 c0,1.152,0.599,1.764,1.127,2.303C10.615,30.301,11,30.693,11,31.5c0,0.276,0.224,0.5,0.5,0.5s0.5-0.224,0.5-0.5 c0-1.215-0.643-1.87-1.159-2.397C10.371,28.623,10,28.244,10,27.5c0-0.731,0.363-1.094,0.822-1.553 c0.451-0.45,0.993-0.998,1.138-1.947h0.606c0.369,0,0.707-0.202,0.881-0.528c0.174-0.325,0.156-0.719-0.049-1.026l-0.431-0.646 c2.039-0.531,4.014-2.085,5.121-4.335c0.312,0.133,0.627,0.252,0.945,0.335l-0.431,0.646c-0.205,0.308-0.224,0.701-0.049,1.026 C18.727,19.798,19.065,20,19.434,20h0.606c0.145,0.949,0.687,1.497,1.138,1.947C21.637,22.406,22,22.769,22,23.5 c0,0.744-0.371,1.123-0.841,1.603C20.643,25.63,20,26.285,20,27.5c0,0.276,0.224,0.5,0.5,0.5s0.5-0.224,0.5-0.5 c0-0.807,0.385-1.199,0.873-1.697C22.401,25.264,23,24.652,23,23.5c0-1.146-0.593-1.738-1.115-2.261 c-0.385-0.384-0.7-0.712-0.824-1.239h0.505c0.369,0,0.707-0.202,0.881-0.528c0.174-0.325,0.156-0.719-0.049-1.026l-0.431-0.646 C25.051,16.996,28,13.863,28,9.5z M10.434,23l1.066-1.6l1.066,1.6H10.434z M12.07,20.957l-0.57,0.076l-0.57-0.076 C8.468,20.631,5,18.101,5,13.5C5,10.944,5.633,5,11.5,5s6.5,5.944,6.5,8.5C18,18.101,14.532,20.631,12.07,20.957z M19.434,19 l1.066-1.6l1.066,1.6H19.434z M21.07,16.957l-0.57,0.076l-0.57-0.076c-0.454-0.06-0.945-0.221-1.44-0.431 C18.815,15.606,19,14.591,19,13.5c0-4.347-1.503-7.407-4.143-8.743C15.694,2.751,17.32,1,20.5,1C26.367,1,27,6.944,27,9.5 C27,14.101,23.532,16.631,21.07,16.957z M9,7C7.895,7,7,7.895,7,9s0.895,2,2,2c1.105,0,2-0.895,2-2S10.105,7,9,7z M9,10 c-0.551,0-1-0.449-1-1s0.449-1,1-1s1,0.449,1,1S9.551,10,9,10z"></path> </g></svg>
                    </span>
                    </td>
            </tr>
            <tr>
                <td style="padding: 10px; color: #a5a5a3;">
                    <span>
                    <svg style="opacity:0.3;" width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M1.58594 10.5453L10.9393 1.43968C11.0786 1.30029 11.244 1.18971 11.426 1.11427C11.6081 1.03883 11.8032 1 12.0003 1C12.1973 1 12.3925 1.03883 12.5745 1.11427C12.7566 1.18971 12.922 1.30029 13.0613 1.43968L22.4146 10.5453" stroke="#71717A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path> <path d="M16.5 4.62866V3.12866H20V7.84375" stroke="#71717A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path> <path d="M3 9.5V18.069V21.6552C3 22.0118 3.1724 22.3539 3.47928 22.6061C3.78616 22.8583 4.20237 23 4.63636 23H19.3636C19.7976 23 20.2138 22.8583 20.5207 22.6061C20.8276 22.3539 21 22.0118 21 21.6552V9.5" stroke="#71717A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path> <path d="M15 23V18C15 16.3431 13.6569 15 12 15V15C10.3431 15 9 16.3431 9 18V23" stroke="#71717A" stroke-width="1.5"></path> <path d="M9 10.25C8.58579 10.25 8.25 10.5858 8.25 11C8.25 11.4142 8.58579 11.75 9 11.75V10.25ZM15 11.75C15.4142 11.75 15.75 11.4142 15.75 11C15.75 10.5858 15.4142 10.25 15 10.25V11.75ZM9 11.75H15V10.25H9V11.75Z" fill="#71717A"></path> <path d="M9 7.25C8.58579 7.25 8.25 7.58579 8.25 8C8.25 8.41421 8.58579 8.75 9 8.75V7.25ZM15 8.75C15.4142 8.75 15.75 8.41421 15.75 8C15.75 7.58579 15.4142 7.25 15 7.25V8.75ZM9 8.75H15V7.25H9V8.75Z" fill="#71717A"></path> </g></svg>
                    </span>
                    <b>WOHNORT</b><span
                        style="margin-left: 15px;">{{ $data["place_of_residence"] ?? ''}}</span></td>
                <td style="padding: 10px; color: #a5a5a3;">
                    
                    <b>GEBURTSORT</b><span
                        style="margin-left: 37px;">{{$data["date_of_birth"] ?? ''}}</span>
                    <span>
                        <svg style="opacity:0.3;" version="1.1" id="Uploaded to svgrepo.com" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="20px" height="20px" viewBox="0 0 32 32" xml:space="preserve" fill="none"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <style type="text/css"> .feather_een{fill:#0B1719;} </style> <path class="feather_een" d="M28,9.5C28,3.552,25.196,0,20.5,0c-3.114,0-5.377,1.581-6.561,4.368C13.197,4.125,12.382,4,11.5,4 C6.804,4,4,7.552,4,13.5c0,4.363,2.949,7.496,6.033,8.299l-0.431,0.646c-0.205,0.308-0.224,0.701-0.049,1.026 C9.727,23.798,10.065,24,10.434,24h0.505c-0.124,0.527-0.439,0.856-0.824,1.239C9.593,25.762,9,26.354,9,27.5 c0,1.152,0.599,1.764,1.127,2.303C10.615,30.301,11,30.693,11,31.5c0,0.276,0.224,0.5,0.5,0.5s0.5-0.224,0.5-0.5 c0-1.215-0.643-1.87-1.159-2.397C10.371,28.623,10,28.244,10,27.5c0-0.731,0.363-1.094,0.822-1.553 c0.451-0.45,0.993-0.998,1.138-1.947h0.606c0.369,0,0.707-0.202,0.881-0.528c0.174-0.325,0.156-0.719-0.049-1.026l-0.431-0.646 c2.039-0.531,4.014-2.085,5.121-4.335c0.312,0.133,0.627,0.252,0.945,0.335l-0.431,0.646c-0.205,0.308-0.224,0.701-0.049,1.026 C18.727,19.798,19.065,20,19.434,20h0.606c0.145,0.949,0.687,1.497,1.138,1.947C21.637,22.406,22,22.769,22,23.5 c0,0.744-0.371,1.123-0.841,1.603C20.643,25.63,20,26.285,20,27.5c0,0.276,0.224,0.5,0.5,0.5s0.5-0.224,0.5-0.5 c0-0.807,0.385-1.199,0.873-1.697C22.401,25.264,23,24.652,23,23.5c0-1.146-0.593-1.738-1.115-2.261 c-0.385-0.384-0.7-0.712-0.824-1.239h0.505c0.369,0,0.707-0.202,0.881-0.528c0.174-0.325,0.156-0.719-0.049-1.026l-0.431-0.646 C25.051,16.996,28,13.863,28,9.5z M10.434,23l1.066-1.6l1.066,1.6H10.434z M12.07,20.957l-0.57,0.076l-0.57-0.076 C8.468,20.631,5,18.101,5,13.5C5,10.944,5.633,5,11.5,5s6.5,5.944,6.5,8.5C18,18.101,14.532,20.631,12.07,20.957z M19.434,19 l1.066-1.6l1.066,1.6H19.434z M21.07,16.957l-0.57,0.076l-0.57-0.076c-0.454-0.06-0.945-0.221-1.44-0.431 C18.815,15.606,19,14.591,19,13.5c0-4.347-1.503-7.407-4.143-8.743C15.694,2.751,17.32,1,20.5,1C26.367,1,27,6.944,27,9.5 C27,14.101,23.532,16.631,21.07,16.957z M9,7C7.895,7,7,7.895,7,9s0.895,2,2,2c1.105,0,2-0.895,2-2S10.105,7,9,7z M9,10 c-0.551,0-1-0.449-1-1s0.449-1,1-1s1,0.449,1,1S9.551,10,9,10z"></path> </g></svg>
                    </span>
                    </td>
            </tr>
            <tr>
                <td style="padding: 10px; color: #a5a5a3;">
                    <span>
                        <svg style="opacity:0.3;" version="1.1" id="Icons" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 32 32" xml:space="preserve" width="20px" height="20px" fill="none"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <style type="text/css"> .st0{fill:none;stroke:#000000;stroke-width:2;stroke-linejoin:round;stroke-miterlimit:10;} .st1{fill:none;stroke:#000000;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;} .st2{fill:none;stroke:#000000;stroke-width:2;stroke-miterlimit:10;} </style> <circle class="st0" cx="16" cy="21" r="3"></circle> <path class="st0" d="M12.7,14h6.6c1.2,0,2.1,0.4,2.1,1.7v0c0,1.3,1,2.3,2.2,2.3H29c0-4.4-3.4-8-7.6-8H10.6C6.4,10,3,13.6,3,18h5.4 c1.2,0,2.2-1.1,2.2-2.3v0C10.6,14.4,11.5,14,12.7,14z"></path> <line class="st1" x1="23" y1="27" x2="23" y2="29"></line> <line class="st1" x1="9" y1="27" x2="9" y2="29"></line> <path class="st0" d="M9,18c-1.5,2.4-3,6.1-3,9v0h20v0c0-2.9-1.5-6.6-3-9"></path> </g></svg>
                    </span>
                    <b>TELEFON</b><span
                        style="margin-left: 30px;">{{$data["phone_number"] ?? ''}}</span></td>
                <td style="padding: 10px; color: #a5a5a3;">
                    
                    <b>NATIONALITÄT</b><span
                        style="margin-left: 28px;">{{$data["nationality"] ?? ''}}</span>
                    <span>
                    <svg style="opacity:0.3;" width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M1.58594 10.5453L10.9393 1.43968C11.0786 1.30029 11.244 1.18971 11.426 1.11427C11.6081 1.03883 11.8032 1 12.0003 1C12.1973 1 12.3925 1.03883 12.5745 1.11427C12.7566 1.18971 12.922 1.30029 13.0613 1.43968L22.4146 10.5453" stroke="#71717A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path> <path d="M16.5 4.62866V3.12866H20V7.84375" stroke="#71717A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path> <path d="M3 9.5V18.069V21.6552C3 22.0118 3.1724 22.3539 3.47928 22.6061C3.78616 22.8583 4.20237 23 4.63636 23H19.3636C19.7976 23 20.2138 22.8583 20.5207 22.6061C20.8276 22.3539 21 22.0118 21 21.6552V9.5" stroke="#71717A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path> <path d="M15 23V18C15 16.3431 13.6569 15 12 15V15C10.3431 15 9 16.3431 9 18V23" stroke="#71717A" stroke-width="1.5"></path> <path d="M9 10.25C8.58579 10.25 8.25 10.5858 8.25 11C8.25 11.4142 8.58579 11.75 9 11.75V10.25ZM15 11.75C15.4142 11.75 15.75 11.4142 15.75 11C15.75 10.5858 15.4142 10.25 15 10.25V11.75ZM9 11.75H15V10.25H9V11.75Z" fill="#71717A"></path> <path d="M9 7.25C8.58579 7.25 8.25 7.58579 8.25 8C8.25 8.41421 8.58579 8.75 9 8.75V7.25ZM15 8.75C15.4142 8.75 15.75 8.41421 15.75 8C15.75 7.58579 15.4142 7.25 15 7.25V8.75ZM9 8.75H15V7.25H9V8.75Z" fill="#71717A"></path> </g></svg>
                    </span>
                    </td>
            </tr>
            <tr>
                <td style="padding: 10px; color: #a5a5a3;">
                    <span>
                        <svg style="opacity:0.3;" version="1.1" id="_x32_" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="20px" height="20px" viewBox="0 0 512 512" xml:space="preserve" fill="none"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <style type="text/css">  .st0{fill:#000000;}  </style> <g> <path class="st0" d="M459.078,72.188H52.922v249.5h406.156V72.188z M419.172,281.766H92.844V112.109h326.328V281.766z"></path> <path class="st0" d="M452.438,351.641H59.578L0,407.609v32.203h512v-32.203L452.438,351.641z M205.188,402.422l9.766-15.625h82.094 l9.781,15.625H205.188z"></path> </g> </g></svg>
                    </span>
                    <b>E-MAIL</b><span style="margin-left: 50px;">{{$data["email"] ?? ''}}</span>
                </td>
                <td style="padding: 10px; color: #a5a5a3;"><b>FAMILIENSTAND</b><span
                        style="margin-left: 15px;">{{$data["martial_status"] ?? ''}}</span>
                    <span>
                        <svg style="opacity:0.3;" width="20px" height="20px" viewBox="0 0 24 24" id="Layer_1" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" fill="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><defs><style>.cls-1{fill:none;stroke:#999;stroke-miterlimit:10;stroke-width:1.91px;}</style></defs><circle class="cls-1" cx="8.66" cy="15.34" r="7.16"></circle><circle class="cls-1" cx="16.3" cy="12.48" r="6.2"></circle><polygon class="cls-1" points="16.77 6.27 15.82 6.27 12.96 3.41 13.91 1.5 18.68 1.5 19.64 3.41 16.77 6.27"></polygon></g></svg>
                    </span>
                    </td>
            </tr>
        </table>

        <p style="text-align: center;text-transform: uppercase;font-size: 25px; color: #5a677d;  font-size: 30px;">
            {{$data["lastname"]." ". $data["firstname"]}}</p>
        <p style="text-align: center; color: #a5a5a3; margin-top: -20px"><i>{{ $data["short_bio"] ?? '' }}</i></p>
        <div style="margin: 25px 0px;">
            <hr style="border: 1px solid #444;" />
            <p style="text-align: center; color: #5a677d;">EXPERTISE</p>
            <hr style="border: 1px solid #444;" />
        </div>
        <div style="padding: 0.1px; background-color: #ddd;">
            <ul style="list-style-type:none;">
                @foreach ($data["expertises"] as $exp)
                    <li>— {{$exp ?? ''}}</li>
                @endforeach
            </ul>
        </div>
        <div style="margin: 25px 0px;">
            <hr style="border: 1px solid #444;" />
            <p style="text-align: center; color: #5a677d;"> BERUFLICHER WERDEGANG</p>
            <hr style="border: 1px solid #444;" />
        </div>
        <div style="padding: 10px;background-color: #ddd;">
            <ul style="list-style-type: none;">
                @foreach ($data["professional_experience"] as $exp)
                    <li style="margin-top: 3px">
                        <span>{{ ($exp["start_date"] ?? '')."-". ($exp["end_date"] ?? '') }} - <b>{{$exp["position"] ?? ''}}</b> - {{ $exp["company"] ?? '' }}, {{$exp["company_location"] ?? ''}}</b></span>
                    </li>
                @endforeach
            </ul>
        </div>
        <div style="margin: 25px 0px; margin-bottom: -5px;">
            <hr style="border: 1px solid #444;" />
            <p style="text-align: center; color: #5a677d;">SKILL SET</p>
            <hr style="border: 1px solid #444;" />
        </div>
        @foreach (($data["skills"] ?? '') as $ind => $sk)
            <div style="display: inline-block; margin-top: 30px;">
                <span style="padding: 10px 28px; background-color: #bbbbb9;margin-right: 10px;">{{$sk}}</span>
            </div>
        @endforeach
    </div>
    <div class="page-break"></div>
    <div style="padding: 0px 20px;">
        <div>
            <p style="text-transform: uppercase; font-size: 40px; color: #5a677d;">{{$data["lastname"]. " ". $data["firstname"]}}</p>
            <p style="margin-top: -20px;">{{$data["short_bio"] ?? ''}}</p>
        </div>
        <div>
            <table style="text-align:center;width: 100%; border-top: 2px solid #666;border-bottom: 1px solid #666;">
                <tr>
                    <td style="padding: 10px;  text-align: start;">{{$data["phone_number"] ?? ''}}</td>
                    <td style="padding: 10px;  text-align: start;">{{$data["email"] ?? ''}}</td>
                    <td style="padding: 10px;  text-align: start;">{{$data["address"] ?? ''}}</td>
                    <td style="padding: 10px;  text-align: start;">
                        @if ($data["linkedin_link"])
                            <a href="{{$data["linkedin_link"] ?? ''}}">Linkedin</a>
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        <div style="margin-top: 50px;">
            <p><b>{{ $data["job_application"]["company_name"] ?? '' }}</b></p>
            <p><b>z.Hd. {{ $data["job_application"]["employer_name"] ?? '' }}</b></p>
            <p>{{ $data["job_application"]["company_location"] ?? '' }}</p>
            <p>{{ $data["job_application"]["company_zipcode"] ?? '' }}</p>
        </div>
        <p style="text-align:right;">
            {{ $data["job_application"]["date"] }}
        </p>

        <div style="margin-top: 100px;">

            <p style="font-size: 25px; color: #5a677d;">BEWERBUNG ALS {{ $data["job_application"]["job_title"] ?? '' }}</p>
            <div>
                {{$data["job_application"]["cover_letter"] ?? ''}}
            </div>
            
            <p>Mit freundlichen Gr&uuml;&szlig;en<br>{{$data["firstname"]. " ". $data["lastname"]}}</p>
        </div>

    </div>


    <div class="page-break"></div>
    <div style="padding: 0px 20px;">
        <p style="text-transform: uppercase; font-size: 40px; margin-top: 3em; color: #5a677d;">{{$data["lastname"] }}<br/> {{ $data["firstname"]}}</p>
        <p style="font-size:18px;">{{$data["short_bio"] ?? ''}}</p>
    </div>
    <div style="padding: 0px 20px; ">
        <table style="text-align:center;width: 100%; border-top: 2px solid #666;border-bottom: 1px solid #666;">
            <tr>
                <td style="padding: 10px; text-align: start;">
                    <p class="icon-box">
                        <span class="icon">
                            <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M16.5562 12.9062L16.1007 13.359C16.1007 13.359 15.0181 14.4355 12.0631 11.4972C9.10812 8.55901 10.1907 7.48257 10.1907 7.48257L10.4775 7.19738C11.1841 6.49484 11.2507 5.36691 10.6342 4.54348L9.37326 2.85908C8.61028 1.83992 7.13596 1.70529 6.26145 2.57483L4.69185 4.13552C4.25823 4.56668 3.96765 5.12559 4.00289 5.74561C4.09304 7.33182 4.81071 10.7447 8.81536 14.7266C13.0621 18.9492 17.0468 19.117 18.6763 18.9651C19.1917 18.9171 19.6399 18.6546 20.0011 18.2954L21.4217 16.883C22.3806 15.9295 22.1102 14.2949 20.8833 13.628L18.9728 12.5894C18.1672 12.1515 17.1858 12.2801 16.5562 12.9062Z" fill="#1C274C"></path> </g></svg>
                        </span>
                        {{$data["phone_number"] ?? ''}}
                    </p>
                </td>
                <td style="padding: 10px; text-align: start;">
                    <p class="icon-box">
                        <span class="icon">
                            <svg width="20px" height="20px" viewBox="0 -2.5 20 20" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" fill="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <title>email [#1572]</title> <desc>Created with Sketch.</desc> <defs> </defs> <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"> <g id="Dribbble-Light-Preview" transform="translate(-340.000000, -922.000000)" fill="#000000"> <g id="icons" transform="translate(56.000000, 160.000000)"> <path d="M294,774.474 L284,765.649 L284,777 L304,777 L304,765.649 L294,774.474 Z M294.001,771.812 L284,762.981 L284,762 L304,762 L304,762.981 L294.001,771.812 Z" id="email-[#1572]"> </path> </g> </g> </g> </g></svg>
                        </span>
                    {{$data["email"] ?? ''}}
                    </p>
                </td>
                <td style="padding: 10px; text-align: start;">
                    <p class="icon-box">
                        <span class="icon">
                        <svg width="20px" height="20px" viewBox="-4 0 32 32" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:sketch="http://www.bohemiancoding.com/sketch/ns" fill="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <title>location</title> <desc>Created with Sketch Beta.</desc> <defs> </defs> <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd" sketch:type="MSPage"> <g id="Icon-Set-Filled" sketch:type="MSLayerGroup" transform="translate(-106.000000, -413.000000)" fill="#000000"> <path d="M118,422 C116.343,422 115,423.343 115,425 C115,426.657 116.343,428 118,428 C119.657,428 121,426.657 121,425 C121,423.343 119.657,422 118,422 L118,422 Z M118,430 C115.239,430 113,427.762 113,425 C113,422.238 115.239,420 118,420 C120.761,420 123,422.238 123,425 C123,427.762 120.761,430 118,430 L118,430 Z M118,413 C111.373,413 106,418.373 106,425 C106,430.018 116.005,445.011 118,445 C119.964,445.011 130,429.95 130,425 C130,418.373 124.627,413 118,413 L118,413 Z" id="location" sketch:type="MSShapeGroup"> </path> </g> </g> </g></svg>
                        </span>
                    {{$data["address"] ?? ''}}
                    </p>
                </td>
                <td style="padding: 10px; text-align: start;">
                    @if ($data["linkedin_link"])
                        <p class="icon-box">
                            <span class="icon">
                                <svg fill="#000000" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="-143 145 512 512" xml:space="preserve" width="20px" height="20px"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <g> <path d="M113,145c-141.4,0-256,114.6-256,256s114.6,256,256,256s256-114.6,256-256S254.4,145,113,145z M272.8,560.7 c-20.8,20.8-44.9,37.1-71.8,48.4c-27.8,11.8-57.4,17.7-88,17.7c-30.5,0-60.1-6-88-17.7c-26.9-11.4-51.1-27.7-71.8-48.4 c-20.8-20.8-37.1-44.9-48.4-71.8C-107,461.1-113,431.5-113,401s6-60.1,17.7-88c11.4-26.9,27.7-51.1,48.4-71.8 c20.9-20.8,45-37.1,71.9-48.5C52.9,181,82.5,175,113,175s60.1,6,88,17.7c26.9,11.4,51.1,27.7,71.8,48.4 c20.8,20.8,37.1,44.9,48.4,71.8c11.8,27.8,17.7,57.4,17.7,88c0,30.5-6,60.1-17.7,88C309.8,515.8,293.5,540,272.8,560.7z"></path> <rect x="-8.5" y="348.4" width="49.9" height="159.7"></rect> <path d="M15.4,273c-18.4,0-30.5,11.9-30.5,27.7c0,15.5,11.7,27.7,29.8,27.7h0.4c18.8,0,30.5-12.3,30.4-27.7 C45.1,284.9,33.8,273,15.4,273z"></path> <path d="M177.7,346.9c-28.6,0-46.5,15.6-49.8,26.6v-25.1H71.8c0.7,13.3,0,159.7,0,159.7h56.1v-86.3c0-4.9-0.2-9.7,1.2-13.1 c3.8-9.6,12.1-19.6,27-19.6c19.5,0,28.3,14.8,28.3,36.4v82.6H241v-88.8C241,369.9,213.2,346.9,177.7,346.9z"></path> </g> </g></svg>
                            </span>
                            <a href="{{$data["linkedin_link"] ?? ''}}">Linkedin</a>
                        </p>
                    @endif
                </td>
            </tr>
        </table>
    </div>
    <table style="width: 100%;">
        <tr style="width: 30%">
            <td style="width: 30%;padding: 15px;border-right: 2px solid #666; vertical-align: top;">
                <p style="font-size: 25px; color: #5a677d;">PERSÖNLICHES</p>
                <p><b style="margin-right: 7px;">Geburtsdatum</b><span>{{$data["date_of_birth"] ?? ''}}</span></p>
                <p><b style="margin-right: 37px;">Geburtsort</b><span>{{$data["place_of_birth"] ?? ''}}</span></p>
                <p><b style="margin-right: 37px;">Nationalit&auml;</b><span>{{$data["nationality"] ?? ''}}</span></p>
                <p><b style="margin-right: 10px;">Familienstand</b><span>{{$data["martial_status"] ?? ''}}</span></p>
                <hr style="border: 1px solid #666; margin-bottom: -2px;" />
            </td>
            <td style="width: 65%;padding: 15px; vertical-align: top;">
                <p style="font-size: 25px; color: #5a677d;">ÜBER MICH</p>
                <p> <br>
                    {{ $data["professional_summary"] ?? ''}}
                </p>
                <hr style="border: 1px solid #666;" />
            </td>
        </tr>
        <tr>
            <td style="padding: 15px;border-right: 2px solid #666;">
                <!-- Expertise -->
                <div>
                    <p style="font-size: 25px; color: #5a677d; margin-top: -20px;">EXPERTISE</p>
                    {{-- <span><b>@expertisetitle</b></span> --}}
                    <ul>
                        @foreach (($data["expertises"] ?? []) as $ex)
                            <li>{{ $ex }} </li>
                        @endforeach
                    </ul>

                    <span><b>Soft Skills</b></span>
                    <ul>
                        @foreach (($data["soft_skills"]??[]) as $ex)
                            <li>{{ $ex }} </li>
                        @endforeach
                    </ul>

                    <span><b>Fachliche Kompetenzen</b></span>
                    <ul>
                        @foreach (($data["professional_skills"] ?? []) as $ex)
                            <li>{{ $ex }} </li>
                        @endforeach
                    </ul>



                    <!-- <hr style="border: 1px solid #666;" /> -->
                </div>

                <!-- Languages -->

                <div>
                    <p style="font-size: 25px; color: #5a677d;">SPRACHEN</p>
                    @foreach (($data["languages"] ?? []) as $ex)
                        <p><b style="margin-right: 7px;">{{$ex['language']}}</b><span>{{ $ex['proficiency_level'] }}</span></p>
                    @endforeach
                    

                    <hr style="border: 1px solid #666;" />
                </div>

                <!-- Certificates -->
                <div>
                    <p style="font-size: 25px; color: #5a677d;">ZERTIFIKATE</p>
                    <ul>
                        @foreach (($data["certifications"] ?? []) as $ex)
                        <li>
                            <p>{{$ex}}</p>
                        </li>
                        @endforeach
                    </ul>
                    <hr style="border: 1px solid #666;" />
                </div>

                <!-- UNIVERSITY -->
                <div>
                    <p style="font-size: 25px; color: #5a677d;">STUDIUM</p>
                    @foreach (($data["education"] ?? []) as $ex)
                    <div>
                        <p><span>{{$ex['start_date']}}</span>-<span>{{$ex['end_date']}}</span></p>
                        <p style="color: #5a677d; margin-top: -10px;">{{ $ex['degree_held'] }}</p>
                        <ul>
                            @foreach (($ex["courses_studied"] ?? []) as $csex)
                            <li>{{$csex}}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endforeach
                </div>
                    <hr style="border: 1px solid #666;" />
                </div>

                <!-- INTERESTS -->
                <div>
                    <p style="font-size: 25px; color: #5a677d;">INTERESSEN</p>
                    <ul>
                        @foreach ($data["interests"] as $ex)
                        <li>{{$ex}}</li>
                        @endforeach
                    </ul>

                    <hr style="border: 1px solid #666;" />
                </div>

                <!-- OTHER -->
                <div>
                    <p style="font-size: 25px; color: #5a677d;">SONSTIGES</p>
                    <ul>
                        @foreach ($data["other_hobbies"] as $ex)
                        <li>{{$ex}}</li>
                        @endforeach
                    </ul>
                </div>

            </td>
            <td style="width: 70%;padding: 15px; vertical-align: top;">
                <p style="font-size: 25px; color: #5a677d; margin-top: -15px">BERUFSERFAHRUNG</p>
                @foreach ($data["professional_experience"] as $exp)
                <div style="margin-left: -50px;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 260 50"
                        style="width:360px; height:50px; display:block;">
                        <!-- Linie + Pfeil, links offen -->
                        <path d="M0 6
                                H160
                                L200 25
                                L160 44
                                H0" fill="none" stroke="#666" stroke-width="2" />

                        <!-- Text: links angeheftet -->
                        <text x="10" y="50%" dominant-baseline="middle" text-anchor="start"
                            style="font-family: Arial, Helvetica, sans-serif; fill:#333;">
                            {{ $exp["start_date"] }} - {{ $exp["end_date"] }}
                        </text>
                    </svg>
                </div>

                <p style="font-size: 22px; color: #5a677d; margin-bottom: -15px;">{{$exp["job_title"]}}</p>
                <p style="font-size: 18px"><i>{{ $exp["company"] }}, {{$exp["company_location"]}}</i></p>
                <ul style="list-style-type:none;">
                    @foreach (($exp["achievements"] ?? []) as $ex)
                    <li>{{$ex}}</li>
                    @endforeach
                </ul>
                @endforeach
            </td>
        </tr>
    </table>
</body>

</html>