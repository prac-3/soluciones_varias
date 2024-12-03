<?php
// Requerimientos de dependencias necesarias para manejar correos y PDFs
require __DIR__ . '/PHPMailer/Exception.php';
require __DIR__ . '/PHPMailer/PHPMailer.php';
require __DIR__ . '/PHPMailer/SMTP.php';
require_once 'vendor/autoload.php'; // Autoload de Composer para cargar bibliotecas automáticamente

//uso de clases necesarioas PHPMaile,fpdi.exception
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use setasign\Fpdi\Tcpdf\Fpdi;


//parametros que se optienen de la api 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $params = [
        'Sociedad' => $_POST['Sociedad'] ?? '',
        'cb_area' => $_POST['cb_area'] ?? 'false',
        'cb_basico' => $_POST['cb_basico'] ?? 'false',
        'cb_tcon' => $_POST['cb_tcon'] ?? 'false',
        'cb_vinc' => $_POST['cb_vinc'] ?? 'false',
        'Correo' => $_POST['Correo'] ?? '',
    ];


    //verificacion para que estos campos siempre esten selecionados para su consulta
    if (empty($params['Sociedad']) || empty($params['Correo'])) {
        echo "Error: Debe seleccionar una sociedad y proporcionar un correo.";
        exit;
    }


    //optenemos la url completa con los parametros , construcion de la api,realizar la solicitud
    $apiUrl = "http://aspod.hospital.com:50000/RESTAdapter/appinternos_dev/v1/pdfcertificadolaboral";
    $apiUrl .= '?' . http_build_query($params);

    //ejecucion de la solicitud
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, "po_appintern:3AqHS4MJIM"); //autetificacion basica
    $result = curl_exec($ch);

    if (curl_errno($ch)) {
        echo "Error de conexión: " . curl_error($ch);
        curl_close($ch);
        exit;
    }
    // decodificacion de la respuesta JSON de la api 
    curl_close($ch);
    $response = json_decode($result, true);

    if (!$response || $response['TypeMessage'] !== 'S' || $response['IdMessage'] !== '000') {
        echo "Error en la generación del certificado: " . ($response['Message'] ?? 'Respuesta no válida');
        exit;
    }

    // Decodificar PDF Base64
    //optiene el pdf base64 con el parametro externo que viene de la api como identificacionEmpleado,con el que se encripta el pdf

    $pdfBase64 = $response['PDFCetificadoLaboral'];
    $pdfData = base64_decode($pdfBase64);
    $numeroIdentificacion = $response['IndentificacionEmpleado']; // Contraseña del PDF

    // Crear archivo temporal para el PDF original
    //metodos que agilizan ya vienen con los componenetes de composer 

    $tempPdfPath = sys_get_temp_dir() . '/CertificadoLaboralOriginal.pdf';
    file_put_contents($tempPdfPath, $pdfData);

    // Crear un archivo temporal para guardar el PDF protegido
    $encryptedPdfPath = sys_get_temp_dir() . '/CertificadoLaboralProtegido.pdf';

    // Proteger el PDF usando FPDI y TCPDF
    //por medio de composer se descargar estos paquetes o pluggis
    $pdf = new Fpdi();
    $pageCount = $pdf->setSourceFile($tempPdfPath);

    //anula los pie de pagina y los encabezados ya que sin esto se generan margenes al enviar el pdf 
    //como el pdf se guarda en dos partes 
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    //generamos una nueva pagina, y se genera una nueva plantilla sin margen alguno como se muestra en sus propiedades
    for ($i = 1; $i <= $pageCount; $i++) {
        $templateId = $pdf->importPage($i);
        $pdf->AddPage();
        $pdf->useTemplate($templateId, 0, 0, null, null, false);
    }
    //este metoodo SetProtection se utiliza para proteger el pdf  asigana la contraseña , esto es de la biblioteca de FPDI
    $pdf->SetProtection(['copy', 'print'], $numeroIdentificacion, $numeroIdentificacion);
    $pdf->Output($encryptedPdfPath, 'F');

    // Configurar y enviar el correo con PHPMailer
    //esto se intala por medio de composer ya que este paquete de PHPMailer nos permite enviar el correo facilmente 
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = '172.28.77.34';
        $mail->SMTPAuth = false;
        $mail->Port = 25;
        $mail->setFrom('soluciones.nomina@sanvicentefundacion.com', 'Soluciones Nómina');
        $mail->addAddress($params['Correo']);
        $mail->addAttachment($encryptedPdfPath, 'CertificadoLaboralProtegido.pdf');
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'Certificación Laboral Adjunta - Pruebas';


        //Cuerpo del correo 
        $mail->Body = '
          <style>
            .soluciones-nomina {
                font-weight: bold;
            }
            .san-vicente {
                font-size: 16px;
            }
            /* Agrega más estilos si es necesario */
        </style>

        <p>Cordial saludo,</p>
        <p>Adjunto al presente PDF Certificado Laboral.</p>
        <p>Para abrirlo solo debes escribir el número de tu documento de identidad en el archivo adjunto.</p><br/>
        <p><b>Importante:</b></p>
        <p>Cualquier modificación o alteración de la información contenida en el certificado laboral adjunto será considerada una falta grave al Reglamento Interno de Trabajo del Hospital. Esto conllevará la aplicación de las medidas disciplinarias establecidas en dicho reglamento. Además, dicha acción podría configurar el delito de falsedad en documento privado, conforme al artículo 289 del Código Penal (Ley 599 de 2000).</p>
        <p>Por tal motivo, el documento adjunto está sujeto a verificación tanto por parte de las entidades ante quienes se presente como por el Hospital San Vicente Fundación.</p>
        <p>Quedamos atentos a cualquier inquietud.</p>
        <p>Atentamente,</p>
         <table style="color:rgb(34,34,34);border:none;border-collapse:collapse">

         <tbody>
          <tr>
          <td style="vertical-align:middle;padding:5pt">
          <p dir="ltr" style="line-height:1.2;margin-top:0pt;margin-bottom:0pt;text-align:center">
            <span style="font-size:11pt;font-family:Arial;background-color:transparent;vertical-align:middle;white-space:pre-wrap">
                <img src="https://ci3.googleusercontent.com/meips/ADKq_NaUqXBY3vNAWJJx3ToGLgRZ4bc5mpAhlrK6RMbugsX_0rbRQfgHvyUy_EnEyUWQ20hI1Myhlim8HgI0avMAUrw2KkU4U5EY0wC2Xef-1Vl0N80hZPzl=s0-d-e1-ft#https://drive.google.com/uc?id=1BdUyk6FMFhWkcwRKyutxpJSuxNlPGqtl" alt="hospitalsanvicentefundacion.jpg" style="border:none" class="CToWUd" data-bit="iit">
            </span>
        </p>
        <div dir="ltr">
            <div></div>
        </div>
        <p></p>
        </td>
        <td style="vertical-align:bottom;padding:15pt">
        <p dir="ltr" style="line-height:1.2;margin-top:0pt;margin-bottom:0pt">
            <b style="color:rgb(0,105,65);font-family:&quot;Microsoft Sans Serif&quot;,sans-serif;font-size:13.3333px">Soluciones Nómina</b><br>
        </p>
        <p dir="ltr" style="line-height:1.2;margin-top:0pt;margin-bottom:0pt">
            <span style="color:rgb(151,191,13);font-family:&quot;Microsoft Sans Serif&quot;,sans-serif;font-size:13.3333px">SAN VICENTE FUNDACIÓN</span>
        </p>
        <p dir="ltr" style="line-height:1.2;margin-top:0pt;margin-bottom:0pt">
        <a href="mailto:soluciones.nomina@sanvicentefundacion.com" 
         style="color:rgb(17,85,204);font-family:&quot;Microsoft Sans Serif&quot;,sans-serif;font-size:13.3333px" 
         target="_blank">
         soluciones.nomina@.sanvicentefundacion.com
         </a>
        <br>
        </p>

        <span>
            <p dir="ltr" style="line-height:1.2;margin-top:0pt;margin-bottom:0pt">
                <span style="font-size:10pt;font-family:sans-serif;color:rgb(91,91,95);background-color:transparent;vertical-align:baseline;white-space:pre-wrap">Tel: </span>
                <span style="font-size:10pt;font-family:sans-serif;color:rgb(91,91,95);background-color:transparent;vertical-align:baseline;white-space:pre-wrap">(574) 444 13 33</span>
            </p>
            <p dir="ltr" style="line-height:1.2;margin-top:0pt;margin-bottom:0pt">
                <span style="font-size:10pt;font-family:sans-serif;color:rgb(91,91,95);background-color:transparent;vertical-align:baseline;white-space:pre-wrap">Calle 64 # 51 D - 154 Medellín - Colombia</span>
            </p>
            <p dir="ltr" style="line-height:1.2;margin-top:0pt;margin-bottom:0pt"></p>
            <p dir="ltr" style="line-height:1.2;margin-top:0pt;margin-bottom:0pt">
                <a style="color:rgb(34,34,34)">
                    <span style="font-size:11pt;font-family:sans-serif;color:rgb(161,188,49);background-color:transparent;vertical-align:baseline;white-space:pre-wrap">www.sanvicentefundacion.com</span>
                   </a>
                   </p>
                    </span>
                  </td>
             </tr>
            </tbody>
            </table>

        ';

        $mail->send();
        echo "El certificado ha sido enviado al correo proporcionado.";
    } catch (Exception $e) {
        echo "Error al enviar el correo: {$mail->ErrorInfo}";
    }

    // Eliminar archivos temporal
    unlink($tempPdfPath);
    unlink($encryptedPdfPath);
}
