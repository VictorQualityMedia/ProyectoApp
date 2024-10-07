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
        // Asegúrate de que la petición sea AJAX y que el usuario tenga los permisos necesarios.
        if ($request->isXmlHttpRequest() && $this->isGranted('ROLE_SUPERADMIN')) {
            $logo = $request->files->get('logo'); // Obtén el archivo subido

            // Verifica si se subió un archivo
            if ($logo) {
                // Verifica que el archivo sea una imagen válida (opcional)
                if (!in_array($logo->getMimeType(), ['image/png', 'image/jpeg', 'image/gif'])) {
                    return new JsonResponse(['error' => 'Formato de imagen no permitido.']);
                }

                // Define el directorio de destino
                $publicDir = $this->getParameter('kernel.project_dir') . '/public';
                var_dump($publicDir);

                // Renombrar el logo antiguo si existe
                $oldLogoPath = $publicDir . '/logo.png';
                if (file_exists($oldLogoPath)) {
                    rename($oldLogoPath, $publicDir . '/' . uniqid() . '.png');
                }

                // Guardar el nuevo logo como "logo.png"
                $newFilename = 'logo.png';
                try {
                    // Mueve el archivo subido al directorio público
                    $logo->move($publicDir, $newFilename);
                    return new JsonResponse(['exito' => 'Logo subido correctamente!']);
                } catch (\Exception $e) {
                    return new JsonResponse(['error' => 'Error al subir el archivo.']);
                }
            } 
            else {
                return new JsonResponse(['error' => 'No se ha recibido ningún archivo.']);
            }

        } else {
            return new Response("No tienes autorización.");
        }
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
