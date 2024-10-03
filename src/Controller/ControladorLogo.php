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
use App\Entity\Tipo_jornada;
use Symfony\Component\Validator\Constraints\Date;

class ControladorLogo extends AbstractController
{

    /**
     * @Route("/cambiar_logo", name="cambiar_logo")
     */
    public function cambiar_logo(Request $request)
    {
        $logoFile = $request->files->get('logo'); // Obtén el archivo subido

        if ($logoFile) {
            // Define el directorio de destino
            $publicDir = $this->getParameter('kernel.project_dir') . '/public';
            var_dump($publicDir);

            // Renombrar el logo antiguo
            $oldLogoPath = $publicDir . '/logo.png';
            if (file_exists($oldLogoPath)) {
                rename($oldLogoPath, $publicDir . '/' . uniqid() . '.png');
            }

            // Guardar el nuevo logo como "logo.png"
            $newFilename = 'logo.png';

            try {
                $logoFile->move($publicDir, $newFilename);
                $this->addFlash('success', 'Logo actualizado correctamente');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error al subir el logo');
            }
        }

        return $this->redirectToRoute('change_logo');
    }

}
