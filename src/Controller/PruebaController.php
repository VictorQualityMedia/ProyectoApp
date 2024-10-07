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

class PruebaController extends AbstractController
{

    /**
     * @Route("/prueba_shade", name="prueba_shade")
     */
    public function prueba(Request $request, EntityManagerInterface $em)
    {
        $entidad_color = $em->getRepository(Color::class)->find(1);
        if (!$entidad_color) {
            return new Response ("Ha habido una anomalía con la base de datos en la tabla COLOR.");
        }
        
        // Al funcionar con AJAX, es necesario que envie alguna respuesta, de lo contrario, dará el error 500
        $color = $entidad_color->getColor();
            
        if ($color) {
            // Guarda el valor hexadecimal, pero con un %23 en lugar de un #. Por alguna razón tampoco se puede modificar esta cadena.
            return new Response ($color);
        }
        else {
            return new Response(['error' => 'Ha habido un problema.']);
        }

    }

}
