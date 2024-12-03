<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generar Certificado Laboral</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="style.css">

</head>

<body>
    <div class="background-image"></div>
    <div class="banner">
        <h1>Hospital San Vicente Fundación</h1>
    </div>
    <div class="container">
        <h2>Generar Certificado Laboral</h2>
        <form id="certificadoForm" method="POST" action="api.php">
            <div class="mb-3">
                <label for="sede" class="form-label">Seleccione la sede</label>
                <select class="form-select" id="sede" name="Sociedad" required>
                    <option value="">-- Seleccione una opción --</option>
                    <option value="HSVM">Hospital San Vicente Medellín</option>
                    <option value="HSVR">San Vicente Sede Rionegro</option>
                </select>
            </div>
            <label class="form-label">Selecciona la información a incluir en su certificado:</label>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="cb_area" name="cb_area" value="true">
                <label class="form-check-label" for="cb_area">Incluir información de área</label>
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="cb_basico" name="cb_basico" value="true">
                <label class="form-check-label" for="cb_basico">Incluir información del salario</label>
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="cb_tcon" name="cb_tcon" value="true">
                <label class="form-check-label" for="cb_tcon">Incluir tipo de contrato</label>
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="cb_vinc" name="cb_vinc" value="true">
                <label class="form-check-label" for="cb_vinc">Incluir horas de vinculación</label>
            </div>
            <div class="mb-3">
                <label for="correo" class="form-label">Correo Electrónico</label>
                <input type="email" class="form-control" id="correo" name="Correo" placeholder="Ejemplo: nombre@sanvicentefundacion.com" required>
            </div>
            <button type="submit" class="btn btn-success w-100">Generar Certificado</button>
        </form>
        <div class="btn-container mt-3">
            <button onclick="window.location.href='index.html';" class="btn btn-danger btn-cerrar w-100">Cerrar</button>
        </div>
    </div>
    <footer>
        <div class="footer__copyright">
            <img src="img/logoHospitalSVFCuadrado.png" alt="Logo San Vicente Fundación" style="width: 80px; margin-right: 15px;">
            <h6>
                © San Vicente Fundación Todos los derechos Reservados 2021
                <span>
                    <a href="https://www.sanvicentefundacion.com/terminos-y-condiciones" target="_blank">Términos y Condiciones</a> -
                    <a href="https://www.sanvicentefundacion.com/politica-de-proteccion-de-datos-personales" target="_blank">Política de Protección de Datos Personales</a>
                </span>
            </h6>
        </div>
    </footer>
</body>

<script>
    document.getElementById("certificadoForm").addEventListener("submit", function(e) {
        e.preventDefault();

        // Verificar si al menos un checkbox está marcado
        const checkboxes = document.querySelectorAll(".form-check-input");
        const algunCheckboxMarcado = Array.from(checkboxes).some(checkbox => checkbox.checked);

        if (!algunCheckboxMarcado) {
            Swal.fire({
                title: "Seleccione una opción",
                text: "Por favor, marque al menos un campo para incluir en el certificado laboral.",
                icon: "warning",
                confirmButtonText: "Aceptar",
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'btn-aceptar'
                }
            });
            return; // Detener el envío del formulario
        }

        // Si pasa la validación, proceder con el envío
        const formData = new FormData(this);
        fetch("api.php", {
                method: "POST",
                body: formData,
            })
            .then(response => response.text())
            .then(data => {
                if (data.includes("El certificado ha sido enviado al correo proporcionado.")) {
                    Swal.fire({
                        title: "¡Éxito!",
                        text: "El certificado se ha enviado correctamente al correo proporcionado.",
                        icon: "success",
                        confirmButtonText: "Aceptar",
                        buttonsStyling: false,
                        customClass: {
                            confirmButton: 'btn-aceptar'
                        }
                    }).then(() => {
                        document.getElementById("certificadoForm").reset();
                    });
                } else {
                    Swal.fire({
                        title: "Error",
                        text: "Sede incorrecta o eamil",
                        icon: "error",
                        confirmButtonText: "Aceptar",
                        buttonsStyling: false,
                        customClass: {
                            confirmButton: 'btn-aceptar'
                        }
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    title: "Error",
                    text: "No se pudo conectar con el servidor.",
                    icon: "error",
                    confirmButtonText: "Aceptar",
                    buttonsStyling: false,
                    customClass: {
                        confirmButton: 'btn-aceptar'
                    }
                });
            });
    });
</script>

</body>

</html>