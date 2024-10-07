<?php

namespace App\Controller;

use App\Entity\Contrato;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Entity\Usuario;
use App\Entity\Departamentos;
use App\Entity\Festivos;
use App\Entity\Jornadas;
use App\Entity\Entrada;
use App\Entity\Color;
use App\Entity\Tipo_jornada;
use Symfony\Component\Validator\Constraints\Date;
use Doctrine\ORM\EntityManagerInterface;

class AdminController extends AbstractController
{

    /**
     * @Route("/cambiar_logo", name="cambiar_logo")
     */
    public function cambiar_logo(Request $request)
    {
        // Verifica si el archivo fue enviado correctamente
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $logoFile = $_FILES['logo'];

            // Directorio de destino en la carpeta public
            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/logos/';
            $fileName = 'logo.png';  // Nombre fijo para el archivo

            // Verifica si la carpeta de destino existe, si no, créala
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            // Borra todos los archivos existentes en el directorio
            array_map('unlink', glob($uploadDir . "*"));

            // Mueve el archivo subido al directorio con el nombre "logo.png"
            if (move_uploaded_file($logoFile['tmp_name'], $uploadDir . $fileName)) {
                // Responder con éxito
                return new JsonResponse(['message' => 'Archivo subido exitosamente', 'filename' => $fileName]);
            } else {
                return new JsonResponse(['error' => 'Error moviendo el archivo'], 500);
            }
        }

        return new JsonResponse(['error' => 'No se recibió el archivo correctamente'], 400);
    }

    /**
     * @Route("/cambiar_color", name="cambiar_color")
     */
    public function cambiar_color(Request $request, EntityManagerInterface $em)
    {
        // Cambia el color de forma correcta en la base de datos.
        $entidad_color = $em->getRepository(Color::class)->find(1);
        if (!$entidad_color) {
            return new Response ("Ha habido una anomalía con la base de datos en la tabla COLOR.");
        }
        
        // Al funcionar con AJAX, es necesario que envie alguna respuesta, de lo contrario, dará el error 500
        if ($request->isXmlHttpRequest() && $this->isGranted('ROLE_SUPERADMIN')) {
            $color = $request->request->get('color'); // Obtén el valor del campo de color
            
            if ($color) {
                $color = urldecode($color);
                $entidad_color->setColor($color);
                $em->flush();
                // Guarda el valor hexadecimal, pero con un %23 en lugar de un #. Por alguna razón tampoco se puede modificar esta cadena.
                return new JsonResponse(['exito' => 'Color cambiado!']);
            }
            else {
                return new JsonResponse(['error' => 'Ha habido un problema.']);
            }

        } 
        else {
            return new Response("No tienes autorización.");
        }
    }

    /**
     * @Route("/devolver_color", name="devolver_color")
     */
    public function devolver_color(Request $request, EntityManagerInterface $em)
    {
        $entidad_color = $em->getRepository(Color::class)->find(1);
        if (!$entidad_color) {
            return new JsonResponse(['error' => 'No se encuentra el propio .']);
        }
        
        // Al funcionar con AJAX, es necesario que envie alguna respuesta, de lo contrario, dará el error 500
        if ($request->isXmlHttpRequest()) {
            $color = $entidad_color->getColor();
            
            if ($color) {
                // Guarda el valor hexadecimal, pero con un %23 en lugar de un #. Por alguna razón tampoco se puede modificar esta cadena.
                return new JsonResponse(['exito' => $color]);
            }
            else {
                return new JsonResponse(['error' => 'Ha habido un problema.']);
            }

        } 
        else {
            return new Response("No tienes autorización.");
        }
    }

}
