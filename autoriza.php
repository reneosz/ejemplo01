<?php
session_start();
if($_SESSION['verificado']=="ok"){include_once("../../php/conexion.php");}
else{
    header('Location: ../../');
}
$solicitud = $_POST['sol'];
$fecha_verif = date("Y-m-d");
$fecha_receta = $_POST['fecha_receta'];
$estatus = $_POST['estatus'];
$f_inicio = $_POST['fecha_inicio'];
$f_fin = $_POST['fecha_final'];
$obs_justific = $_POST['obs_just'];
$obs_sist = $_POST['obs_sist'];
$docentes = $_POST['my-select'];
$docentes_envio = "";
$tutor = $_POST['tutor'];
$correos_copia= "depto.fortalecimiento@utvam.edu.mx,m.benitez@utvam.edu.mx".",".$tutor;

    for ($i=0;$i<count($docentes);$i++)    
    {     
        $docentes_envio.=$docentes[$i].',';
        
        $sqlcorr= "Select Correo from Docente where idDocente=".$docentes[$i];
        $resultcorr = mysqli_query($link,$sqlcorr) or die('Error en la consulta.');
        $dato = mysqli_fetch_array($resultcorr, MYSQLI_ASSOC);
        $correos_copia = $correos_copia.','.$dato['Correo'];

    }

$docentes_envio = substr($docentes_envio, 0, -1);

//procedimiento para calculo de folio del justificante primero extrae coordinacion, despues genera conteo para evitar join

$sqlcoord = "Select Coordinacion,Solicitud.correo as correo,carrera.Correo_coordinacion as correocordi,concat(estudiantes.Nombre,' ',estudiantes.Apaterno,' ',estudiantes.Amaterno) as alumno from carrera,estudiantes,Solicitud where carrera.idcarrera = estudiantes.carrera_idcarrera 
            and estudiantes.Matricula = Solicitud.estudiantes_Matricula and idSolicitud =".$solicitud;
            
            $rescoord = mysqli_query($link,$sqlcoord) or die('Error en la consulta.');
            $row = mysqli_fetch_array($rescoord, MYSQLI_ASSOC);
            $correo_destino= $row['correo'];
            $alumno = $row['alumno'];
            $correos_copia = $correos_copia.';'.$row['correocordi'];

$sqlcount = "Select count(*) as num from Justificante,carrera,estudiantes,Solicitud where carrera.idcarrera = estudiantes.carrera_idcarrera 
and estudiantes.Matricula = Solicitud.estudiantes_Matricula and Solicitud.idSolicitud = Solicitud_idSolicitud
and Coordinacion = '".$row['Coordinacion']."'";

            $rescount = mysqli_query($link,$sqlcount) or die('Error en la consulta.');
            $row2 = mysqli_fetch_array($rescount, MYSQLI_ASSOC);
            $folio = $row2['num'] + 1;

$folio_coordinacion = $folio.'/'.$row['Coordinacion'].'/'.date('Y');

//Insert de justificante

$sqljust = "INSERT INTO Justificante(Docentes,Tutor,Observaciones,Fecha_elab,Anotaciones,Periodo_in,Periodo_fin,Autorizado,Folio_justificante,Fecha_receta,Solicitud_idSolicitud) 
VALUES ('".$docentes_envio."','".$tutor."','".$obs_justific."','".date("Y-m-d")."','".$obs_sist."','".$f_inicio."','".$f_fin."','".$estatus."','".$folio_coordinacion."','".$fecha_receta."','".$solicitud."')";

$resjustificante = mysqli_query($link,$sqljust) or die('Error en la consulta.');

if($resjustificante){
    $sqlupd = "UPDATE Solicitud SET Estatus = '1' WHERE idSolicitud = '".$solicitud."'";
    mysqli_query($link,$sqlupd) or die('Error en la consulta.');
    
    if($estatus == "1"){
    $nombre_origen    = "campusvirtual@utvam.edu.mx";
    $email_origen     = "campusvirtual@utvam.edu.mx";

    require('phpmailer/class.phpmailer.php');
    require('phpmailer/class.smtp.php');
    
    $mail = new PHPMailer();
    $mail->SetLanguage('en','phpmailer.lang-en.php');

    //Validación por SMTP:
    //$mail->IsSMTP();
    $mail->Mailer = "mail";
    $mail->SMTPAuth = true;
    $mail->SMTPSecure = "ssl";
    $mail->Host = "smtp.gmail.com"; // SMTP a utilizar. Por ej. smtp.elserver.com
    $mail->Username = "campusvirtual@utvam.edu.mx"; // Correo completo a utilizar
    $mail->Password = ""; 
    $mail->Port = 465; // Puerto a utilizar
   




    //$mail->setFrom = $email_origen;  Desde donde enviamos
    $mail->From = $email_origen;
    $mail->FromName = "UTVAM (Justificante)";
    $mail->AddAddress($correo_destino); // Esta es la dirección a donde enviamos
    $mail->AddBCC($correos_copia2); 
    $sol = base64_encode($solicitud);

    $mail->IsHTML(true); // El correo se envía como HTML
    $mail->Subject = "Justificante de inasistencia"; // Este es el titulo del email.
    $body = '
    Estimad@ docente adscrito a la UTVAM,
    este correo ha sido enviado automaticamente, al haber concluido el proceso de solicitud de justificante de inasistencia.

    Solicitamos de su apoyo para justificar las inasistencias del estudiante '.$alumno.', durante el periodo del '.$f_inicio.' al '.$f_fin.'.<br>
    <br>
    Es importante mencionar que el/la estudiante se compromete a entregar sus evidencias de clase.
       

    <br>
    Puede consultar el justificante mediante el siguiente <a href="http://campusvirtual.utvam.edu.mx/justificante/justificante.php?sc='.$sol.'" target="_blank" >enlace.</a>
    <br><br>
    Dirección Académica<br>
    Universidad Tecnológica de la Zona Metropolitana del Valle de México<br><br><br>';
    $mail->Body = $body; // Mensaje a enviar
    $exito = $mail->Send();


    $intentos=1; 
    while ((!$exito) && ($intentos < 5)) {
        sleep(5);
            echo $mail->ErrorInfo.'<br><br>';
            $exito = $mail->Send();
            $intentos=$intentos+1;	
        
    }if(!$exito)
    {
     header('Location: http://campusvirtual.utvam.edu.mx/justificante/control/pages/index.php?env="xx"');
    }
    else
    {
        header('Location: http://campusvirtual.utvam.edu.mx/justificante/control/pages/index.php?env="ok"');
    } 
}else { //justificante no autorizado
    
    $nombre_origen    = "campusvirtual@utvam.edu.mx";
    $email_origen     = "campusvirtual@utvam.edu.mx";

    require('phpmailer/class.phpmailer.php');
    require('phpmailer/class.smtp.php');
    
    $mail = new PHPMailer();
    $mail->SetLanguage('en','phpmailer.lang-en.php');

    //Validación por SMTP:
    //$mail->IsSMTP();
    $mail->Mailer = "mail";
    $mail->SMTPAuth = true;
    $mail->SMTPSecure = "ssl";
    $mail->Host = "smtp.gmail.com"; // SMTP a utilizar. Por ej. smtp.elserver.com
    $mail->Username = "campusvirtual@utvam.edu.mx"; // Correo completo a utilizar
    $mail->Password = ""; 
    $mail->Port = 465; // Puerto a utilizar
   



    //$mail->setFrom = $email_origen;  Desde donde enviamos
    $mail->From = $email_origen;
    $mail->FromName = "UTVAM (Justificante)";
    $mail->AddAddress($correo_destino); // Esta es la dirección a donde enviamos
    $mail->AddBCC($correos_copia2); 
    $sol = base64_encode($solicitud);

    $mail->IsHTML(true); // El correo se envía como HTML
    $mail->Subject = "Respuesta a solicitud de justificante"; // Este es el titulo del email.
    $body = '
    Estimad@ estudiante,
    lamentablemente tu solicitud no cumple con los lineamientos establecidos para que pueda ser aprobada.<br><br>
    Recuerda que para poder aprobar tu solicitud, tienes que adjuntar un documento oficial del IMSS.
    
    <br><br>
    Dirección Académica<br>
    Universidad Tecnológica de la Zona Metropolitana del Valle de México<br><br><br>';
    $mail->Body = $body; // Mensaje a enviar
    $exito = $mail->Send();


    $intentos=1; 
    while ((!$exito) && ($intentos < 5)) {
        sleep(5);
            echo $mail->ErrorInfo.'<br><br>';
            $exito = $mail->Send();
            $intentos=$intentos+1;	
        
    }if(!$exito)
    {
     header('Location: http://campusvirtual.utvam.edu.mx/justificante/control/pages/index.php?env="xx"');
    }
    else
    {
        header('Location: http://campusvirtual.utvam.edu.mx/justificante/control/pages/index.php?env="ok"');
    } 
    
    
}



}


?>