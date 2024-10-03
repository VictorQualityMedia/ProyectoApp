<?php

namespace App\Controller;

use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use DateTimeZone;
use App\Entity\Usuario;
use App\Entity\Festivos;
use App\Entity\Contrato;
use App\Entity\Entrada;
use App\Entity\Jornadas;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class WebController extends AbstractController
{
    /**
     * @Route("/", name="ctrl_login")
     */
    public function login(AuthenticationUtils $authenticationUtils, SessionInterface $session)
    {
        if (!$this->getUser()) {
            $mensaje = $session->getFlashBag()->get('mensaje');
            if ($authenticationUtils->getLastAuthenticationError()) {
                $mensaje = ["Usuario y/o contraseña incorrectos"];
            }
            return $this->render('login.html.twig', ['mensaje' => $mensaje]);
        } else {
            return $this->render('inicio.html.twig');
        }
    }

    /**
     * @Route("/logout", name="ctrl_logout")
     */
    public function logout()
    {

        return $this->render('login.html.twig');
    }

    /**
     * @Route("/admin", name="ctrl_admin")
     */
    public function admin()
    {
        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_SUPERADMIN')) {
            return $this->render("admin.html.twig");
        } else {
            return $this->redirectToRoute('ctrl_login');
        }
    }
    /**
     * @Route("/modificarUsu", name="modificar_usuario")
     */
    public function modificarUsu()
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('ctrl_login');
        }

        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $entityManager = $this->getDoctrine()->getManager();
            $usu = $entityManager->getRepository(Usuario::class)->findOneBy(['codigo' => $_POST['hidden']]);
            $cod_dept = 0;
            if ($usu->getDepartamento() != null) {
                $cod_dept = $usu->getDepartamento()->getCodigo();
            }

            $c = $entityManager->getRepository(Contrato::class)->findOneBy(['usuario' => intval($_POST['hidden'])], ['codigo' => 'DESC']);
            $contrato = 'No tiene';
            $horario = 'No tiene';
            $personal = 0;
            if ($c) {
                $contrato = $c->getContrato();
                $horario = $c->getHorario();
                $personal = $c->getPersonal();
                $inicio = $c->getInicio()->format('Y-m-d H:i:s');
            }


            return $this->render('modificar.html.twig', ["usuario" => $usu, 'cod_usuario' => $_POST['hidden'], 'nombre' => $usu->getNombre(), 'correo' => $usu->getCorreo(), 'dni' => $usu->getDNI(), 'rol' => $usu->getRol(), 'departamento' => $cod_dept, 'departamento_usuario' => $this->getUser()->getDepartamento(), 'horario' => $horario, 'contrato' => $contrato, "personal" => $personal , "inicio" => $inicio]);
        }
        return $this->redirectToRoute('ctrl_admin');
    }
    /**
     * @Route("/perfil", name="ctrl_perfil")
     */
    public function perfil()
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('ctrl_login');
        } else {
            $timezone_identifiers = \DateTimeZone::listIdentifiers();
            return $this->render('perfil.html.twig', ["lista" => $timezone_identifiers]);
        }
    }
    /**
     * @Route("/recuperarClave/{codigo}", name="recuperarClave")
     */
    public function recuperarClave(UserPasswordHasherInterface $passwordHasher, $codigo = null)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $usuario = $entityManager->getRepository(Usuario::class)->findOneBy(array('recuperacion' => $codigo));
        $actual = new \DateTime(date('Y-m-d H:i:s'));
        if ($usuario && $usuario->getExpiracion() > $actual) {
            $usuario->setRecuperacion(0);
            $usuario->setExpiracion(null);
            $entityManager->flush();
            $token = new UsernamePasswordToken($usuario, null, 'main', $usuario->getRoles());
            $this->get('security.token_storage')->setToken($token);
            $this->get('session')->set('_security_main', serialize($token));
            return $this->render('recuperarClave.html.twig', array('recuperacion' => $codigo));
        } else if (isset($_POST['clave'])) {
            $hash = $passwordHasher->hashPassword($this->getUser(), $_POST['clave']);
            $this->getUser()->setClave($hash);
            $entityManager->flush();
        }
        return $this->redirectToRoute('ctrl_login');
    }
    /**
     * @Route("/calendario", name="calendario")
     */
    public function caledario()
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('ctrl_login');
        }

        $entityManager = $this->getDoctrine()->getManager();
        $festivos = $entityManager->getRepository(Festivos::class)->findAll();
        $vacaciones = $entityManager->getRepository(Jornadas::class)->findBy(['usuario' => $this->getUser(), 'tipo_jornada' => 2]);
        $bajas = $entityManager->getRepository(Jornadas::class)->findBy(['usuario' => $this->getUser(), 'tipo_jornada' => 3]);
        $js = [];
        $js['vacaciones'] = [];
        $js['baja'] = [];
        foreach ($festivos as $festivo) {
            $y = $festivo->getFecha()->format('Y');
            $m = $festivo->getFecha()->format('m');
            $d = $festivo->getFecha()->format('d');
            $js['festivos'][] = [$y, $m, $d];
            # code...
        }
        foreach ($vacaciones as $vacacion) {
            $y = $vacacion->getFecha()->format('Y');
            $m = $vacacion->getFecha()->format('m');
            $d = $vacacion->getFecha()->format('d');
            $js['vacaciones'][] = [$y, $m, $d];
            # code...
        }
        foreach ($bajas as $baja) {
            $y = $baja->getFecha()->format('Y');
            $m = $baja->getFecha()->format('m');
            $d = $baja->getFecha()->format('d');
            $js['bajas'][] = [$y, $m, $d];
            # code...
        }

        // Usuarios
        $usuarios = $entityManager->getRepository(Usuario::class)->findBy([], ['nombre' => 'ASC']);
        $usu = [];
        foreach ($usuarios as $i => $u) {
            $usu[$i]['id'] = $u->getCodigo();
            $usu[$i]['nombre'] = $u->getNombre();
        }


        return $this->render('calendario.html.twig', ['fechas' => $js, 'usuarios' => $usu]);
    }
    /**
     * @Route("/prueba", name="prueba")
     */
    public function prueba()
    {
        $entityManager = $this->getDoctrine()->getManager();
        $entrada = $entityManager->getRepository(Entrada::class)->findOneBy(['codigo' => 142]);
        $fecha = new \DateTime('2023-05-25 00:00:00', new \DateTimeZone('Africa/Abidjan'));
        return new JsonResponse([$fecha]);
    }

    /**
     * @Route("/actividad", name="comprobar_actividad")
     */
    public function actividad() {
        $comprobado = false;
        if ($this->getUser()) $comprobado = true;
        return new JsonResponse($comprobado);
    }
}