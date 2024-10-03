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

class UsuarioController extends AbstractController
{

    /**
     * @Route("/borrarUsu", name="borrar_usuario")
     */
    public function borrarUsu(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $entityManager = $this->getDoctrine()->getManager();
            $usu = $entityManager->getRepository(Usuario::class)->findOneBy(['codigo' => $request->request->get('id_a_borrar')]);
            $entityManager->remove($usu);
            $jornadas = $entityManager->getRepository(Jornadas::class)->findBy(['usuario' => $usu]);
            $entityManager->flush();
            return new JsonResponse(['borrado' => true]);
        } else {
            return new Response("No tienes acceso.");
        }
    }
    /**
     * @Route("/filtrarCalendario", name="filtrarCalendario")
     */
    public function filtrarCalendario(Request $request)
    {
        $js = [];
        if ($request->isXmlHttpRequest() && ($this->isGranted('ROLE_SUPERADMIN') || $this->isGranted('ROLE_ADMIN'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $festivos = $entityManager->getRepository(Festivos::class)->findAll();
            $usuario = $entityManager->getRepository(Usuario::class)->findOneBy(['codigo' => $request->request->get('usuario')]);
            $vacaciones = $entityManager->getRepository(Jornadas::class)->findBy(['usuario' => $usuario, 'tipo_jornada' => 2]);
            $bajas = $entityManager->getRepository(Jornadas::class)->findBy(['usuario' => $usuario, 'tipo_jornada' => 3]);
            $js['vacaciones'] = [];
            $js['baja'] = [];
            $js['festivos'] = [];
            foreach ($festivos as $festivo) {
                $y = $festivo->getFecha()->format('Y');
                $m = $festivo->getFecha()->format('m');
                $d = $festivo->getFecha()->format('d');
                $js['festivos'][] = [$y, $m, $d];
            }
            foreach ($vacaciones as $vacacion) {
                $y = $vacacion->getFecha()->format('Y');
                $m = $vacacion->getFecha()->format('m');
                $d = $vacacion->getFecha()->format('d');
                $js['vacaciones'][] = [$y, $m, $d];
            }
            foreach ($bajas as $baja) {
                $y = $baja->getFecha()->format('Y');
                $m = $baja->getFecha()->format('m');
                $d = $baja->getFecha()->format('d');
                $js['bajas'][] = [$y, $m, $d];
            }
        }
        return new JsonResponse($js);
    }

    /**
     * @Route("/crearUsu", name="crear_usu")
     */
    public function crearUsu(Request $request, UserPasswordHasherInterface $passwordHasher)
    {
        if ($request->isXmlHttpRequest() && ($this->isGranted('ROLE_SUPERADMIN') || $this->isGranted('ROLE_ADMIN'))) {
            $entityManager = $this->getDoctrine()->getManager();


            $usuario = new Usuario();
            $usuario->setNombre($request->request->get('nom_usu'));

            // comprobar correo
            $correo = $entityManager->getRepository(Usuario::class)->findOneBy(['correo' => $request->request->get('email_usu')]);

            if ($correo) {
                return new JsonResponse(['error' => 'El correo indicado no está disponible!']);
            }

            $usuario->setCorreo($request->request->get('email_usu'));
            $clave = $request->request->get('clave_usu');
            $hash = $passwordHasher->hashPassword($usuario, $clave);
            $usuario->setClave($hash);

            // comprobar dni
            $dni = $entityManager->getRepository(Usuario::class)->findOneBy(['dni' => $request->request->get('dni_usu')]);

            if ($dni) {
                return new JsonResponse(['error' => 'El DNI indicado no está disponible!']);
            }

            $usuario->setDni($request->request->get('dni_usu'));

            // comprobar departamento existe
            $departamento = $entityManager->getRepository(Departamentos::class)->findOneBy(['nombre' => $request->request->get('nom_dept')]);
            if (!$departamento) {
                return new JsonResponse(['error' => 'El departamento indicado no existe!']);
            }

            $usuario->setDepartamento($departamento);

            // Rol
            $rol = $request->request->get('rol');
            $usuario->setRol($rol);

            $entityManager->persist($usuario);
            $entityManager->flush();

            // Crear contrato

            $c = new Contrato();
            $c->setUsuario($usuario->getCodigo());
            $c->setContrato($request->request->get('contrato'));
            $c->setPersonal($request->request->get('horas_personal'));
            $c->setInicio(new \DateTime($request->request->get('inicio_contrato')));
            $c->setHorario($request->request->get('horario'));
            if (0 > $request->request->get('horas_personal')) {
                return new JsonResponse(['error' => 'Las horas de contrato deben ser correctas!']);
            }

            $entityManager->persist($c);
            $entityManager->flush();

            return new JsonResponse(['exito' => 'Usuario creado!']);
        } else {
            return new Response("No tienes acceso.");
        }
    }
    /**
     * @Route("/crearDepart", name="crear_dept")
     */
    public function crearDepart(Request $request)
    {
        if ($request->isXmlHttpRequest() && $this->isGranted('ROLE_SUPERADMIN')) {
            $entityManager = $this->getDoctrine()->getManager();
            $departamento = $entityManager->getRepository(Departamentos::class)->findOneBy(['nombre' => $request->request->get('nom_dept')]);
            if (!$departamento) {
                $depart = new Departamentos();
                $depart->setNombre($request->request->get('nom_dept'));
                $entityManager->persist($depart);
                $entityManager->flush();
                return new JsonResponse(['exito' => 'Departamento creado!']);
            } else {
                return new JsonResponse(['error' => 'El departamento ya existe!']);
            }
        } else {
            return new Response("No tienes acceso.");
        }
    }
    /**
     * @Route("/borrarDept", name="borrar_dept")
     */
    public function borrarDepart(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $entityManager = $this->getDoctrine()->getManager();
            $departamento = $entityManager->getRepository(Departamentos::class)->findOneBy(['nombre' => $request->request->get('dept')]);
            $entityManager->remove($departamento);
            $entityManager->flush();
            return new JsonResponse(['borrado' => true]);
        } else {
            return new Response("No tienes acceso.");
        }
    }

    /**
     * @Route("/buscarUsu", name="buscar_usu")
     */
    public function filtrarUsuarios(Request $request)
    {
        $entityManager = $this->getDoctrine()->getManager();
        if ($request->isXmlHttpRequest()) {
            // SUPERADMIN
            if ($this->isGranted('ROLE_SUPERADMIN')) {
                if ($request->request->get('buscar_nombre') && $request->request->get('buscar_departamento')) {
                    //BUSCAR POR LOS DOS CAMPOS
                    $nombre = $request->request->get('buscar_nombre');
                    $dept = $request->request->get('buscar_departamento');
                    $qb = $entityManager->getRepository(Usuario::class)->createQueryBuilder('u')->leftJoin('u.departamento', 'd')->andWhere("u.nombre LIKE '%" . $nombre . "%'")->andWhere("d.nombre LIKE '%" . $dept . "%'");
                    $usuarios = $qb->getQuery()->getResult();
                } else if ($request->request->get('buscar_nombre')) {
                    //BUSCAR POR EL NOMBRE
                    $nombre = $request->request->get('buscar_nombre');
                    $qb = $entityManager->getRepository(Usuario::class)->createQueryBuilder('u')->where("u.nombre LIKE '%" . $nombre . "%'");
                    $usuarios = $qb->getQuery()->getResult();
                } else if ($request->request->get('buscar_departamento')) {
                    //BUSCAR POR EL DEPARTAMENTO
                    $dept = $request->request->get('buscar_departamento');
                    $qb = $entityManager->getRepository(Usuario::class)->createQueryBuilder('u')->leftJoin('u.departamento', 'd')->where("d.nombre LIKE '%" . $dept . "%'");
                    $usuarios = $qb->getQuery()->getResult();
                } else {
                    //PILLAR TODOS
                    $usuarios = $entityManager->getRepository(Usuario::class)->findAll();
                    $departamentos = $entityManager->getRepository(Departamentos::class)->findAll();
                }

                $jsonData = [];
                $id = 0;

                $lista_departamentos = [];

                if (isset($departamentos)) {
                    $id_dept = 0;

                    foreach ($departamentos as $dept) {
                        $tmp = [
                            'id' => $dept->getCodigo(),
                            'nombre' => $dept->getNombre()
                        ];
                        $lista_departamentos[$id_dept++] = $tmp;
                    }
                }


                foreach ($usuarios as $usu) {


                    $tmp = [
                        'id' => $usu->getCodigo(),
                        'nombre' => $usu->getNombre(),
                        'correo' => $usu->getCorreo(),
                        'dni' => $usu->getDni(),
                        'rol' => $usu->getRol(),
                    ];

                    if ($usu->getDepartamento() != null) {
                        $tmp['departamento'] = $usu->getDepartamento()->getNombre();
                    } else {
                        $tmp['departamento'] = "NO TIENE";
                    }

                    $jsonData['usuarios'][$id++] = $tmp;
                }

                $jsonData['departamentos'] = $lista_departamentos;
                return new JsonResponse($jsonData);
            } else if ($this->isGranted('ROLE_ADMIN')) {
                //ADMIN
                $usu = $this->getUser();

                $cod_dept = $usu->getDepartamento()->getCodigo();

                if ($request->request->get('buscar_nombre')) {
                    $nombre = $request->request->get('buscar_nombre');
                    $qb = $entityManager->getRepository(Usuario::class)->createQueryBuilder('u')->leftJoin('u.departamento', 'd')->andWhere("u.nombre LIKE '%$nombre%'")->andWhere("d.codigo = $cod_dept");
                    $usuarios = $qb->getQuery()->getResult();
                } else {
                    $qb = $entityManager->getRepository(Usuario::class)->createQueryBuilder('u')->leftJoin('u.departamento', 'd')->andWhere("d.codigo = $cod_dept");
                    $usuarios = $qb->getQuery()->getResult();
                }

                $jsonData = [];
                $id = 0;

                foreach ($usuarios as $usu) {

                    $tmp = [
                        'id' => $usu->getCodigo(),
                        'nombre' => $usu->getNombre(),
                        'correo' => $usu->getCorreo(),
                        'dni' => $usu->getDni(),
                        'rol' => $usu->getRol(),
                        'departamento' => $usu->getDepartamento()->getNombre()
                    ];
                    $jsonData['usuarios'][$id++] = $tmp;
                }

                return new JsonResponse($jsonData);
            }
        }
        return new Response('No tienes permiso.');
    }
    /**
     * @Route("/departs", name="cargar_dept")
     */
    public function cargarDepartamentosJson(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $lista_departamentos = [];

            $entityManager = $this->getDoctrine()->getManager();
            $id_dept = 0;
            $departamentos = $entityManager->getRepository(Departamentos::class)->findAll();


            foreach ($departamentos as $dept) {
                $tmp = [
                    'id' => $dept->getCodigo(),
                    'nombre' => $dept->getNombre()
                ];
                $lista_departamentos[$id_dept++] = $tmp;
            }

            if ($this->isGranted('ROLE_SUPERADMIN')) {
                $tmp = [
                    'id' => 0,
                    'nombre' => 'Ninguno'
                ];
                $lista_departamentos[$id_dept++] = $tmp;
            }


            return new JsonResponse($lista_departamentos);
        } else {
            return new Response('No tienes acceso.');
        }
    }

    /**
     * @Route("/cambiar", name="cambiar_datos")
     */
    public function cambiarDatos(Request $request)
    {
        if ($request->isXmlHttpRequest() && (($this->isGranted('ROLE_SUPERADMIN') || $this->isGranted('ROLE_ADMIN')))) {
            $cod = $request->request->get('cod_usu');
            $entityManager = $this->getDoctrine()->getManager();
            $usuario = $entityManager->getRepository(Usuario::class)->findOneBy(['codigo' => $cod]);

            $nombre = $request->request->get('mod_usu');
            $correo = $request->request->get('mod_correo');
            // comprobar correo
            if ($correo != null) {
                $usuario_correo = $entityManager->getRepository(Usuario::class)->findOneBy(['correo' => $correo]);

                if ($usuario_correo) {
                    return new JsonResponse(['error' => 'El correo ya está en uso!']);
                }

                $usuario->setCorreo($correo);
            }

            $pass = $request->request->get('mod_pass');

            if ($pass != null) {
                $pass = password_hash($pass, PASSWORD_DEFAULT);
                $usuario->setClave($pass);
            }

            $dni = $request->request->get('mod_dni');
            if ($dni != null) {
                // comprobar dni
                $usuario_dni = $entityManager->getRepository(Usuario::class)->findOneBy(['dni' => $dni]);

                if ($usuario_dni) {
                    return new JsonResponse(['error' => 'El DNI ya está en uso!']);
                }
                $usuario->setDNI($dni);
            }


            $rol = $request->request->get('mod_rol') == null ? $this->getUser()->getRol() : $request->request->get('mod_rol');
            $dept = $request->request->get('mod_departamento');


            $dept = $entityManager->getRepository(Departamentos::class)->findOneBy(['codigo' => $dept]);

            $usuario->setNombre($nombre);
            $usuario->setRol($rol);

            if (!$dept && $rol == 2) {
                $usuario->setDepartamento(null);
            } else {
                $usuario->setDepartamento($dept);
            }

            $entityManager->persist($usuario);
            $entityManager->flush();

            $modInicio = $request->request->get('mod_inicio');


            $contrato = $entityManager->getRepository(Contrato::class)->findOneBy(['usuario' => $usuario->getCodigo()], ['codigo' => 'DESC']);
            if (!empty($modInicio)) {
                $inicio = new \DateTime($modInicio);
            } else {
                $inicio = $contrato->getInicio();
            }
            // Contrato
            if (!$contrato || ($request->request->get('mod_contrato') != null && (($contrato->getContrato() != $request->request->get('mod_contrato')) ||
                ($contrato->getContrato() == 2 && $contrato->getPersonal() != $request->request->get('horas_personal')) ||
                ($contrato->getHorario() != $request->request->get('mod_horario')) != $request->request->get('mod_inicio')))) {
                $contrato = new Contrato();
                $contrato->setUsuario($usuario->getCodigo());
                $contrato->setContrato($request->request->get('mod_contrato'));
                $contrato->setPersonal($request->request->get('horas_personal'));
                // $contrato->setInicio(new \DateTime());
                $contrato->setInicio($inicio);
                $contrato->setHorario($request->request->get('mod_horario'));
                $entityManager->persist($contrato);
                $entityManager->flush();
            }

            return new JsonResponse(['exito' => true, 'rol' => $rol, 'dept' => $dept]);
        } else {
            return new Response('No tienes acceso.');
        }
    }
    /**
     * @Route("/actualizarPerfil", name="actualizarPerfil")
     */
    public function actualizarPerfil(Request $request)
    {
        if ($this->getUser()) {

            $entityManager = $this->getDoctrine()->getManager();
            if ($request->request->get('correo') != '') {
                try {
                    $this->getUser()->setCorreo($request->request->get('correo'));
                    $entityManager->flush();
                } catch (\Exception $e) {
                    return new JsonResponse(['error' => 'El correo ya está en uso!']);
                }
            }

            if ($request->request->get('dni') != '') {
                try {
                    $this->getUser()->setDNI($request->request->get('dni'));
                    $entityManager->flush();
                } catch (\Exception $e) {
                    return new JsonResponse(['error' => 'El DNI ya está en uso!']);
                }
            }
            if ($request->request->get('clave') != '') {
                $this->getUser()->setClave(password_hash($request->request->get('clave'), PASSWORD_DEFAULT));
            }

            if ($request->request->get('depart') != 'nada') {
                if ($request->request->get('depart') == '0') {
                    $this->getUser()->setDepartamento(null);
                } else {
                    $this->getUser()->setDepartamento($entityManager->getRepository(Departamentos::class)->findOneBy(['codigo' => $request->request->get('depart')]));
                }
            }


            $timezone_identifiers = \DateTimeZone::listIdentifiers();
            if (in_array($request->request->get('zona'), $timezone_identifiers)) {
                $this->getUser()->setZona($request->request->get('zona'));
                $entityManager->flush();
            } else {
                return new JsonResponse(['error' => 'Zona horaria no existente']);
            }

            $entityManager->flush();
        }
        return new JsonResponse(['exito' => true]);
    }
    /**
     * @Route("/enviarCorreo", name="enviarCorreo")
     */
    public function enviarCorreo(MailerInterface $mailer, SessionInterface $session)
    {
        if (isset($_POST['_username'])) {
            $correo = $_POST['_username'];
        } else {
            $correo = '';
        }
        $entityManager = $this->getDoctrine()->getManager();
        $usuario = $entityManager->getRepository(Usuario::class)->findOneBy(array('correo' => $correo));
        do {
            $recuperacion = rand(1, 2147483647);
        } while ($entityManager->getRepository(Usuario::class)->findOneBy(array('recuperacion' => $recuperacion)));
        if ($usuario) {
            $session->getFlashBag()->add('mensaje', "Revisar bandeja de $correo");
            $ruta = $this->url_origin($_SERVER);
            $ruta = $ruta . "/recuperarClave/$recuperacion";
            $email = (new Email())
                ->from('info@fichar.quality.media')
                ->to($correo)
                ->subject('Cambiar contraseña de tu cuenta')
                ->html("<a href=\"$ruta\">Cambiar contraseña</a>");
            $mailer->send($email);
            $expiracion = new \DateTime(date('Y-m-d H:i:s'));
            $expiracion->add(new \DateInterval('P1D'));
            $usuario->setRecuperacion($recuperacion);
            $usuario->setExpiracion($expiracion);
            $entityManager->flush();
        }
        return $this->redirectToRoute('ctrl_login');
    }
    private function url_origin($s, $use_forwarded_host = false)
    {

        $ssl = (!empty($s['HTTPS']) && $s['HTTPS'] == 'on') ? true : false;
        $sp = strtolower($s['SERVER_PROTOCOL']);
        $protocol = substr($sp, 0, strpos($sp, '/')) . (($ssl) ? 's' : '');

        $port = $s['SERVER_PORT'];
        $port = ((!$ssl && $port == '80') || ($ssl && $port == '443')) ? '' : ':' . $port;

        $host = ($use_forwarded_host && isset($s['HTTP_X_FORWARDED_HOST'])) ? $s['HTTP_X_FORWARDED_HOST'] : (isset($s['HTTP_HOST']) ? $s['HTTP_HOST'] : null);
        $host = isset($host) ? $host : $s['SERVER_NAME'] . $port;

        return $protocol . '://' . $host;
    }
}
