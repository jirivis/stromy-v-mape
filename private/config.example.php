<?php
return [
 'installation_key'=>'', // Jednorázový náhodný klíč, nejméně 32 znaků; po instalaci vymazat.
 'app'=>['name'=>'Stromy v mapě','url'=>'https://example.org','timezone'=>'Europe/Prague'],
 'database'=>['host'=>'localhost','port'=>3306,'name'=>'','user'=>'','password'=>''],
 'updates'=>['repository'=>'jirivis/stromy-v-mape', 'public_key'=>file_get_contents(__DIR__.'/../app/update-publisher.pem'), 'check_on_dashboard'=>false], // public_key: veřejný PEM klíč vydavatele, nikdy soukromý.
 'mail'=>['host'=>'','port'=>587,'username'=>'','password'=>'','from_email'=>'','from_name'=>'Stromy v mapě'],
];
