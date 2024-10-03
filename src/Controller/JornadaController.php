<?php

namespace App\Controller;

use App\Entity\Contrato;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Usuario;
use App\Entity\Jornadas;
use App\Entity\Entrada;
use App\Entity\Festivos;
use App\Entity\Tipo_jornada;
use DatePeriod;
use DateInterval;
use Symfony\Component\Routing\Annotation\Route;

class JornadaController extends AbstractController
{
    const VACACIONES = 'Vacaciones';
    const BAJA = 'Baja';
    const NORMAL = 'Normal';
const RECUPERAR = 'Recuperar';
    const FESTIVO = 1.75; //saber el 75% extra
    const inicioNocturno = "22"; //22 pm
    const finNocturno = "06"; //6 am
    const entradaPredeterminada = 10; //10 am
    const salidaPredeterminada = 18; //10 am
    const SUPERADMIN = "ROLE_SUPERADMIN";
    const ADMIN = "ROLE_ADMIN";
    /**
     * @Route("/editarJornada", name="editar_jornada")
     */
    public function editarJornada(Request $request)
    {
        $js["completado"] = true;

        $entityManager = $this->getDoctrine()->getManager();
        $array_jornadas = $request->request->get('jornadas');
        /** @var array $array_jornadas */
        foreach ($array_jornadas as $jornada) {

            $codigo = $jornada[0];
            $tipo = $jornada[1];

            $tipo = $entityManager->getRepository(Tipo_jornada::class)->findOneBy(['cod_tipo' => $tipo]);
            $confirmado = $jornada[2];
            $entradas = $jornada[3];
            $eliminar = $jornada[4];


            $jornada = $entityManager->getRepository(Jornadas::class)->findOneBy(['codigo' => $codigo]);

            if ($eliminar == 'true') {
                $entityManager->remove($jornada);
            } else if (!$jornada->getFestivo() && $jornada->getJornada() && $jornada->getJornada() != $tipo) {
                $jornada->setJornada($tipo);
                $entradas = $entityManager->getRepository(Entrada::class)->findBy(['cod_jornada' => $codigo]);
                foreach ($entradas as $entrada) {
                    $entityManager->remove($entrada);
                }
                $entrada = new Entrada();
                $entrada->setJornada($jornada->getCodigo());
                //al cambiar iniciarlo a las 8am
                $entrada->setEntrada($jornada->getFecha()->setTime(self::entradaPredeterminada, 0, 0));
                //sumar 8 horas
                $salida = clone $jornada->getFecha();
                $entrada->setSalida($salida->setTime(self::salidaPredeterminada, 0, 0));
                $entityManager->persist($entrada);
            } else {
                $jornada->setConfirmado($confirmado);
                $jornada->setJornada($tipo);
                // modifica las entradas
                /** @var array $entradas */
                foreach ($entradas as $entrada) {

                    // eliminar
                    $entrada_a_buscar = $entityManager->getRepository(Entrada::class)->findOneBy(['codigo' => $entrada[0]]);
                    if ($entrada_a_buscar->getSalida()) {
                        if ($entrada[3] == 'true') {
                            $entityManager->remove($entrada_a_buscar);
                        } else {
                            $inic =  explode(':', $entrada[1]);
                            $hora_inicio = clone $entrada_a_buscar->getEntrada();
                            $hora_inicio->setTime($inic[0], $inic[1]);
                            $hora_fin = new \DateTime($entrada[2]);
                            if ($hora_inicio < $hora_fin) {
                                $entrada_a_buscar->setEntrada($hora_inicio);
                                $entrada_a_buscar->setSalida($hora_fin);
                            }
                        }
                    }
                }
                $check = true;
                $entradas = $entityManager->getRepository(Entrada::class)->findBy(['cod_jornada' => $jornada->getCodigo()]);
                for ($i = 0; $i < count($entradas) && $check; $i++) {
                    for ($y = $i + 1; $y < count($entradas); $y++) {
                        if (!$this->permitirActualizarEntrada($entradas[$y], $entradas[$i])) {
                            $check = false;
                            break;
                        }
                    }
                }

                if ($check) {
                    $entityManager->flush();
                } else {
                    $js["completado"] = false;
                }

                if (!$entityManager->getRepository(Entrada::class)->findOneBy(['cod_jornada' => $jornada->getCodigo()])) {
                    $entityManager->remove($jornada);
                }
            }

            $entityManager->flush();
        }
        return new JsonResponse($js);
    }
    /**
     * @Route("/configurarFestivos", name="configurarFestivos")
     */
    public function configurarFestivos(Request $request)
    {
        //ADMIN

        $js['completado'] = true;

        $inicio =  new \DateTime($request->request->get('fecha_inicio'));
        $entityManager = $this->getDoctrine()->getManager();

        try {
            //code...
            $festivo = new Festivos();
            $festivo->setFecha($inicio);
            $entityManager->persist($festivo);
            $entityManager->flush();

            $jornadas = $entityManager->getRepository(Jornadas::class)->findBy(['fecha' => $inicio]);

            foreach ($jornadas as $jornada) {
                $jornada->setFestivo($festivo->getId());
                $jornada->setJornada(null);
            }
            $entityManager->flush();
        } catch (\Throwable $th) {
            //throw $th;
            $js['completado'] = false;
        }
        return new JSONResponse($js);
    }

    /**
     * @Route("/borrarFestivos", name="borrarFestivo")
     */
    public function borrarFestivo(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $entityManager = $this->getDoctrine()->getManager();
            $fecha = new \DateTime($request->request->get('festivo'));
            $festivo = $entityManager->getRepository(Festivos::class)->findOneBy(['fecha' => $fecha]);
            $jornadas = $entityManager->getRepository(Jornadas::class)->findBy(['festivo' => $festivo->getId()]);
            foreach ($jornadas as $jornada) {
                $jornada->setFestivo(null);
                $jornada->setJornada($entityManager->getRepository(Tipo_jornada::class)->findOneBy(['nombre' => self::NORMAL]));
            }
            $entityManager->remove($festivo);
            $entityManager->flush();
            return new JsonResponse(['borrado' => true]);
        } else {
            return new Response('No tienes acceso.');
        }
    }

    /**
     * @Route("/inicioControl", name="inicioControl")
     */
    public function inicioControl(Request $request)
    {
        $js = array();
        if ($this->getUser()) {
            $entityManager = $this->getDoctrine()->getManager();

            $codigo = $request->request->get('codigo');
            $tipo = $request->request->get('tipo');
            //Si no existe un registro para ese dia lo crea y crea una entrada
            $fecha = new \DateTime(null, new \DateTimeZone($this->getUser()->getZona()));
            $jornada = $entityManager->getRepository(Jornadas::class)->findOneBy(array('usuario' => $this->getUser(), 'fecha' =>  $fecha));
            if (!$jornada && $tipo) {
                $jornada = new Jornadas();
                $jornada->setUsuario($this->getUser());
                $jornada->setFecha($fecha);
                $jornada->setZona($this->getUser()->getZona());
                $entityManager->persist($jornada);
                $entityManager->flush();

                $festivo = $entityManager->getRepository(Festivos::class)->findOneBy(array('fecha' => $fecha));
                if ($festivo) {
                    $jornada->setFestivo($festivo->getId());
                    $jornada->setJornada(null);
                } else {
                    $jornada->setJornada($entityManager->getRepository(Tipo_jornada::class)->findOneBy(array('nombre' => self::NORMAL)));
                }
                $entityManager->flush();
            }
            if ($jornada && ($jornada->getFestivo() || $jornada->getJornada()->getNombre() == self::NORMAL)) {
                $entrada = $entityManager->getRepository(Entrada::class)->findOneBy(array('cod_jornada' => $jornada->getCodigo(), 'salida' => null));
                if (!$entrada && !$codigo && $tipo) {
                    //Registrar entrada                
                    $entrada = new Entrada();
                    $entrada->setJornada($jornada->getCodigo());
                    $entrada->setEntrada($fecha);
                    $entityManager->persist($entrada);
                } else if ($entrada && $entrada->getCodigo() == $codigo) {
                    //si me pasa un codigo de entrada
                    //comprobar que ese codigo de entrada tenga el campo salida null, si lo tiene escribe una hora de salida, sino nada
                    $entrada->setSalida(new \DateTime(null, new \DateTimeZone($jornada->getZona())));
                }
                $entityManager->flush();
                //dar datos si la salida es diferente de null
                if ($entrada && !$entrada->getSalida()) {
                    $js = array('completado' => false, 'codigo' => $entrada->getCodigo(), 'hora' => $entrada->getEntrada(), 'actual' => $entrada->getEntrada()->diff(new \DateTime((new \DateTime(null, new \DateTimeZone($jornada->getZona())))->format('Y-m-d H:i:s'))));
                    //->format('Y-m-d H:i:s')
                } else {
                    $js = array('completado' => true);
                }
            } else {
                $js = array('completado' => true);
            }
            //actualizar datos
        }
        return new JSONResponse($js);
    }

    private function horasATrabajar($inicio, $fin, $usuario)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $qb = $entityManager->getRepository(Contrato::class)->createQueryBuilder('c')->andWhere("c.inicio <= '$inicio'")->andWhere("c.usuario = '$usuario'")->orderBy('c.inicio', 'DESC');

        $contrato = $qb->getQuery()->getResult();
        if ($contrato) $contrato = $contrato[0];

        $total = new \DateTime();
        $ahora = clone $total;
        $inicio = new \DateTime($inicio);
        $fin = new \DateTime($fin);
        $fin->modify('+1 day');
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($inicio, $interval, $fin);

        $jornada = null;
        foreach ($period as $day) {
            if ($var = $entityManager->getRepository(Contrato::class)->findOneBy(array("usuario" => $usuario, "inicio" => $day))) $contrato = $var;
            if ($contrato) {
                switch ($contrato->getHorario()) {
                    case 1:
                        $jornada = ["Wed", "Thu", "Fri", "Sat", "Sun"];
                        break;
                    case 0:
                        $jornada = ["Mon", "Tue", "Wed", "Thu", "Fri"];
                        break;
                    default:
                        $jornada =  ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"];
                        break;
                }
            }
            if (!($entityManager->getRepository(Festivos::class)->findOneBy(['fecha' => $day])) && $contrato && in_array($day->format('D'), $jornada)) {
                switch ($contrato->getContrato()) {
                    case 1:
                        // Máximo 40 h semanales para contrato rotativo
                        if ($contrato->getHorario() == 2) {
                            if ($day->format('D') != "Sat" && $day->format('D') != "Sun") {
                                $total->add(new \DateInterval("PT8H"));
                            }
                        } else {
                            $total->add(new \DateInterval("PT8H"));
                        }
                        break;
                    case 2:
                        if ($contrato->getHorario() == 2) {
                            $horas = $contrato->getPersonal() / 30;
                        } else {
                            $horas = $contrato->getPersonal() / 20;
                        }
                        $total->add(new \DateInterval("PT" . $horas . "H"));
                        # code...
                        break;
                    default:
                        $total->add(new \DateInterval("PT4H"));
                        break;
                }
            }
        }
        return $ahora->diff($total);
    }

    /**
     * @Route("/filtrarJornadas/{inicio}/{fin}/{usuario}", name="filtrarJornadas")
     */
    public function filtrarJornadas($inicio = null, $fin = null, $usuario = null)
    {
        //lo que se devuelve
        $jsResponse = array();
        if ($this->getUser()) {
            $entityManager = $this->getDoctrine()->getManager();
            //Si no le paso un usuario es que me busco yo entre jornadas
            if (!$usuario) {
                $cod_user = $this->getUser()->getCodigo();
            } else {
                $cod_user = $usuario;
            }
            $acceso = false;
            $comprobar_usuario = false;
            if ($this->isGranted(self::SUPERADMIN) || $this->getUser()->getCodigo()) {
                $acceso = true;
            } else if ($this->isGranted(self::ADMIN)) {
                //solo admin
                if ($usuario = $this->comprobar_usuario($cod_user)) {
                    if ($this->getUser()->getDepartamento() && $this->getUser()->getDepartamento()->getNombre() == $usuario->getDepartamento()->getNombre()) {
                        $acceso = true;
                    }
                    $comprobar_usuario = true;
                }
            }
            $jornadas = [];
            if ($acceso) {
                if ($comprobar_usuario || $this->comprobar_usuario($cod_user)) {
                    $qb = $entityManager->getRepository(Jornadas::class)->createQueryBuilder('j')->andWhere("j.fecha >= '$inicio'")->andWhere("j.fecha <= '$fin'")->andWhere("j.usuario = '$cod_user'")->orderBy('j.fecha', 'ASC');
                    $jornadas = $qb->getQuery()->getResult();
                }
            }
            $js = [];

            $total = new \DateTime();
            $actual = clone $total;
            $extra = clone $total;
            $recuperadas = clone $total;
            $nocturno = clone $total;
            $por_trabajar = clone $total;
            $comida = clone $total;
            $normal = clone $total;


            $segundos_extra = 0;
            $segundos_nocturno = 0;
            foreach ($jornadas as $jornada) {
                $j['id'] = $jornada->getCodigo();
                $j['fecha'] = $jornada->getFecha();
                if ($jornada->getObservaciones()) {
                    $j['observaciones'] = $jornada->getObservaciones();
                } else {
                    $j['observaciones'] = '';
                }
                if ($jornada->getConfirmado()) {
                    $j['confirmado'] = 'Aprobado';
                } else {
                    $j['confirmado'] = 'Pendiente';
                }
                if ($jornada->getJornada()) {
                    $j['tipo'] = $jornada->getJornada()->getNombre();
                } else if ($jornada->getFestivo()) {
                    $j['tipo'] = 'Festivo';
                } else {
                    $j['tipo'] = 'Fin de semana';
                }
                if ($jornada->getInicio_comida() && $jornada->getFin_comida() && !$jornada->getFestivo()) $comida->add($jornada->getInicio_comida()->diff($jornada->getFin_comida()));

                $entradas = $entityManager->getRepository(Entrada::class)->findBy(array('cod_jornada' => $jornada->getCodigo()), array('entrada' => 'ASC'));
                $cada_entrada = [];

                $total_entradas = clone $actual;
                $total_entradas_recuperar = clone $actual;
                foreach ($entradas as $entrada) {
                    $datetime = null;
                    if ($entrada->getSalida()) {
                        $salida = $entrada->getSalida();
                        $datetime = $entrada->getEntrada()->diff($entrada->getSalida());
                        if ($entrada->getRecuperar()) {
                            $total_entradas_recuperar->add($datetime);
                            $recuperadas->add($datetime);
                        }
                        $total_entradas->add($datetime);
                        $total->add($datetime);
                        // nocturno
                        $segundos_extra += $this->calcularTiempoFestivoRecursivo($entrada->getEntrada(), $entrada->getSalida());
                        $segundos_nocturno += $this->calcularTiempoNocturnoRecursivo($entrada->getEntrada(), $entrada->getSalida());
                    } else {
                        $salida = '';
                        $datetime = '';
                    }
                    $cada_entrada[] = array('id' => $entrada->getCodigo(), 'entrada' => $entrada->getEntrada(), 'salida' => $salida);
                }
                $j['entradas'] = $cada_entrada;
                $j['tiempo'] = $actual->diff($total_entradas);
                $js[] = $j;
            }
        }
        $jsResponse['jornadas'] = $js;
        $jsResponse['total'] = $actual->diff($total->sub($actual->diff($extra->add(new \DateInterval("PT{$segundos_extra}S"))))->sub($actual->diff($comida)));
        $jsResponse['nocturno'] = $actual->diff($nocturno->add(new \DateInterval("PT{$segundos_nocturno}S")));
        $jsResponse['normal'] = $actual->diff($normal->add($jsResponse['total'])->sub($jsResponse['nocturno']));

        $interval = $this->horasATrabajar($inicio, $fin, $cod_user);
        $jsResponse['horas_laborables'] = $interval;
        $jsResponse['recuperadas'] = $actual->diff($recuperadas);
        $jsResponse['por_trabajar'] = $por_trabajar->add($interval)->diff($total);

        return new JSONResponse($jsResponse);
    }

    /**
     * @Route("/recuperar", name="a_recuperar")
     */
    public function horasARecuperar(Request $request = null)
    {
        $entityManager = $this->getDoctrine()->getManager();
        if (!$request) {
            $usuario = $this->getUser()->getCodigo();
        } else {
            $usuario = $request->request->get('id');
            if (!$usuario) {
                $usuario = $this->getUser()->getCodigo();
            }
        }


        $contrato = $entityManager->getRepository(Contrato::class)->findBy(['usuario' => $usuario], ['inicio' => 'ASC']);

        $contrato = $contrato[0];

        $inicio = $contrato->getInicio();
        $a_recuperar = 0;

        $fin =  new \DateTime();
        $fin->modify('+1 day');
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($inicio, $interval, $fin);

        foreach ($period as $day) {
            if ($var = $entityManager->getRepository(Contrato::class)->findOneBy(['usuario' => $usuario, 'inicio' => $day])) $contrato = $var;

            if ($contrato->getContrato() == 0) {
                $h_diarias = 4;
            } else if ($contrato->getContrato() == 1) {
                $h_diarias = 8;
            } else {
                if ($contrato->getHorario() != 2) {
                    $h_diarias = $contrato->getPersonal() / 20;
                } else {
                    $h_diarias = $contrato->getPersonal() / 30;
                }
            }

            switch ($contrato->getHorario()) {
                case 1:
                    $jornada = ["Wed", "Thu", "Fri", "Sat", "Sun"];
                    break;
                case 0:
                    $jornada = ["Mon", "Tue", "Wed", "Thu", "Fri"];
                    break;
                default:
                    $jornada =  ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"];
                    break;
            }

            $j = $entityManager->getRepository(Jornadas::class)->findOneBy(['fecha' => $day, 'usuario' => $usuario]);



            if (($j && $j->getJornada() == null) || ($j && $j->getJornada()->getTipo() != 2 && $j->getJornada()->getTipo() != 3)) {
                $entradas = $entityManager->getRepository(Entrada::class)->findBy(array('cod_jornada' => $j->getCodigo()), array('entrada' => 'ASC'));
                $minutos_trabajados = 0;
                foreach ($entradas as $e) {
                    $entrada = $e->getEntrada();
                    $salida = $e->getSalida();
                    if ($salida) {
                        $interval = $salida->diff($entrada);
                        if ($e->getRecuperar()) {
                            $a_recuperar -= ($interval->d * 24 * 60 + $interval->h * 60 + $interval->i);
                        } else {
                            $minutos_trabajados += $interval->d * 24 * 60 + $interval->h * 60 + $interval->i;
                        }
                    }
                }

                if (!in_array($day->format('D'), $jornada)) {
                    $a_recuperar += $minutos_trabajados;
                } else {
                    if ($j->getJornada() == null) {
                        $a_recuperar += $minutos_trabajados;
                    } else if ($minutos_trabajados > $h_diarias * 60) {
                        if ($j->getInicio_comida()) {
                            $comida = (int) (date_create('@0')->add($j->getInicio_comida()->diff($j->getFin_comida())))->getTimestamp();
                            $minutos_trabajados -= ($comida / 60);
                        }
                        $a_recuperar += ($minutos_trabajados - $h_diarias * 60);
                    }
                }
                if ($a_recuperar < 0) {
                    $a_recuperar = 0;
                }
            }
        }

        return new JsonResponse(['h' => floor($a_recuperar / 60), 'm' => $a_recuperar % 60]);
    }


    /**
     * @Route("/seleccionarTipo", name="seleccionarTipo")
     */
    public function seleccionarTipo()
    {
        $js = [];
        $entityManager = $this->getDoctrine()->getManager();
        $tipos = $entityManager->getRepository(Tipo_jornada::class)->findAll();
        foreach ($tipos as $tipo) {
            # code...
            $js[] = array('id' => $tipo->getTipo(), 'nombre' => $tipo->getNombre());
        }
        return new JSONResponse($js);
    }
    /**
     * @Route("/crear_jornada", name="crear_jornada_manual")
     */
    public function crearJornadaManual(Request $request)
    {
        $js['completado'] = false;
        $js['tipo'] = 1;
        if ($request->isXmlHttpRequest()) {
            $entityManager = $this->getDoctrine()->getManager();

            $tipo = $request->request->get('tipo_jornada');
            $inicio = new \DateTime($request->get('fecha_inicio'));
            $fin = new \DateTime($request->get('fecha_fin'));
            $observaciones = $request->request->get('observaciones');
            $inicio_comida = $request->request->get('inicio_comida') == "0" ? null : new \DateTime($request->request->get('inicio_comida'));
            $fin_comida = $request->request->get('fin_comida') == "0" ? null : new \DateTime($request->request->get('fin_comida'));
            // RECUPERAR
            $recuperar = $request->request->get('recuperar');
            if ($recuperar == "Recuperar") {
                // Comprobar si inicio/fin le corresponden a días laborables
                // Buscar contrato
                $usuario = $this->getUser()->getCodigo();
                $dia_inicio = $inicio->format('Y-m-d');
                $qb = $entityManager->getRepository(Contrato::class)->createQueryBuilder('c')->andWhere("c.inicio <= '$dia_inicio'")->andWhere("c.usuario = '$usuario'")->orderBy('c.inicio', 'DESC');

                $contrato = $qb->getQuery()->getResult();
                if ($contrato) $contrato = $contrato[0];

                $jornada = [];
                if ($contrato) {
                    switch ($contrato->getHorario()) {
                        case 1:
                            $jornada = ["Wed", "Thu", "Fri", "Sat", "Sun"];
                            break;
                        case 0:
                            $jornada = ["Mon", "Tue", "Wed", "Thu", "Fri"];
                            break;
                        default:
                            $jornada =  ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"];
                            break;
                    }
                }


                $dia = $inicio->format('D');
                $es_festivo = $entityManager->getRepository(Festivos::class)->findOneBy(['fecha' => $inicio]);

                if (in_array($dia, $jornada) && !$es_festivo) {
                    $limite = $this->horasARecuperar()->getContent();
                    $json = json_decode($limite, TRUE);
                    $limite = $json['h'] * 60 + $json['m'];

                    $interval = $fin->diff($inicio);

                    $minutos = $interval->d * 24 * 60 + $interval->h * 60 + $interval->i;
                    if ($minutos <= $limite) {
                        $jornada = $entityManager->getRepository(Jornadas::class)->findOneBy(['fecha' => $inicio, 'usuario' => $this->getUser()->getCodigo()]);
                        if ($jornada && $jornada->getTipo_jornada() != 1) {
                            return new JsonResponse(['error' => 'El día indicado no es laborable']);
                        }

                        if (!$jornada) {
                            $jornada = new Jornadas();
                            $jornada->setObservaciones(null);
                            $jornada->setConfirmado(null);
                            $jornada->setFecha($inicio);
                            $jornada->setUsuario($this->getUser());
                            if ($contrato->getHorario() == 1 && ($dia == "Sat" || $dia == "Sun")) {
                                $jornada->setTipo_jornada(null);
                            } else {
                                $jornada->setJornada($entityManager->getRepository(Tipo_jornada::class)->findOneBy(['nombre' => self::NORMAL]));
                            }
                            $jornada->setFestivo(null);
                            $entityManager->persist($jornada);
                            $entityManager->flush();
                        }
                        $entrada = new Entrada();
                        $entrada->setJornada($jornada->getCodigo());
                        $entrada->setEntrada($inicio);
                        $entrada->setSalida($fin);
                        $entrada->setRecuperar(1);
                        $entityManager->persist($entrada);
                        $entityManager->flush();
                        return new JsonResponse(['completado' => true]);
                    } else {
                        return new JsonResponse(['error' => 'No puedes recuperar más de lo que te corresponde!']);
                    }
                } else {
                    return new JsonResponse(['error' => 'El día indicado no es laborable']);
                }
            }
            if ($fin > new \DateTime()) {
                $js['completado'] = false;
                $js['tipo'] = 2;
                return new JsonResponse($js);
            }
            if ($tipo != self::NORMAL) {
                $fin = $fin->modify('+1 day');
            }
            // Bucle sobre los dias
            $period = new DatePeriod($inicio, new DateInterval('P1D'), $fin);
            foreach ($period as $dia) {
                // Buscar el tipo de jornada que tiene ese dia
                $festivo = $entityManager->getRepository(Festivos::class)->findOneBy(['fecha' => $dia]);

                $tipo_jornada = $entityManager->getRepository(Tipo_jornada::class)->findOneBy(['nombre' => $tipo]);

                $jornada = $entityManager->getRepository(Jornadas::class)->findOneBy(['fecha' => $dia, 'usuario' => $this->getUser()]);
                if (!$jornada) {
                    $jornada = new Jornadas();
                    $jornada->setUsuario($this->getUser());
                    $jornada->setFecha($dia);
                    $jornada->setObservaciones($observaciones);
                    $jornada->setZona($this->getUser()->getZona());
                }
                $tipo_jornada_previa = null;
                if ($festivo) {
                    $jornada->setJornada(null);
                    $jornada->setFestivo($festivo->getId());
                } else {
                    if ($jornada->getJornada() && ($jornada->getJornada() != $tipo_jornada ||  $jornada->getJornada()->getNombre() == self::VACACIONES ||  $jornada->getJornada()->getNombre() == self::BAJA)) {
                        //borrar las previas
                        $jornada->setComida(null);
                        $jornada->setInicio_comida(null);
                        $jornada->setFin_comida(null);
                        $entradas = $entityManager->getRepository(Entrada::class)->findBy(['cod_jornada' => $jornada->getCodigo()]);
                        foreach ($entradas as $entrada) {
                            $entityManager->remove($entrada);
                        }
                    }
                    if ($jornada->getJornada()) $tipo_jornada_previa = $jornada->getJornada();
                    $jornada->setJornada($tipo_jornada);
                }
                $entrada = new Entrada();
                $check = false;
                if ($festivo || $tipo_jornada->getNombre() == self::NORMAL) {
                    $jornada->setInicio_comida($inicio_comida);
                    $jornada->setFin_comida($fin_comida);
                    $entrada->setEntrada($inicio);
                    $entrada->setSalida($fin);
                } else {
                    $h_entrada = $jornada->getFecha();
                    $h_salida = clone $jornada->getFecha();
                    $entrada->setEntrada($h_entrada->setTime(self::entradaPredeterminada, 0, 0));
                    $entrada->setSalida($h_salida->setTIme(self::salidaPredeterminada, 0, 0));
                }
                if ((clone $inicio)->add(new \DateInterval("PT10M")) <= $fin && $inicio < $fin) $check = true;

                if ($jornada->getJornada() && $jornada->getJornada()->getNombre() != self::NORMAL || (!$festivo && $tipo_jornada_previa != $jornada->getJornada())) {
                    //si vacaciones o baja
                    $js['completado'] = true;
                    $entityManager->persist($jornada);
                    $entityManager->flush();
                    $entrada->setJornada($jornada->getCodigo());
                    $entityManager->persist($entrada);
                    $entityManager->flush();
                } else {
                    //si normal o festivo
                    if (!$this->permitirEntrada($jornada, $entrada) || !$check) {
                        if (!$check) {
                            $js['tipo'] = 1;
                        } else {
                            $js['tipo'] = 0;
                        }
                        $js['completado'] = false;
                    } else {
                        $js['completado'] = true;
                        $entityManager->persist($jornada);
                        $entityManager->flush();
                        $entrada->setJornada($jornada->getCodigo());
                        $entityManager->persist($entrada);
                        $entityManager->flush();
                    }
                }
                if ($festivo || $tipo_jornada->getNombre() == self::NORMAL) {
                    return new JsonResponse($js);
                }
            }
        }
        return new JsonResponse($js);
    }

    /**
     * @Route("/comprobarJornada", name="comprobarJornada")
     */
    public function comprobarJornada(Request $request)
    {
        $js = [];
        $entityManager = $this->getDoctrine()->getManager();
        $jornada = $entityManager->getRepository(Jornadas::class)->findOneBy(['fecha' => new \DateTime(), 'usuario' => $this->getUser()]);
        //si es baja o vacaciones
        if ($jornada && $jornada->getJornada() && $jornada->getJornada()->getNombre() != self::NORMAL) {
            $js['existe'] = true;
            $js['tipo'] = 'no laborable';
            $js["mensaje"] = $jornada->getJornada()->getNombre();
        } else {
            $js['existe'] = true;
            $js['jornada'] = self::NORMAL;
            $js['tipo'] = 'laborable';
        }
        return new JsonResponse($js);
    }
    /**
     * @Route("/observacion", name="cargarObservacion")
     */
    public function cargarObservacion(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $entityManager = $this->getDoctrine()->getManager();
            $fecha = new \DateTime();
            if ($request->request->get('fecha')) $fecha = new \DateTime($request->request->get('fecha'));
            $jornada = $entityManager->getRepository(Jornadas::class)->findOneBy(['usuario' => $this->getUser(), 'fecha' => $fecha]);
            if ($jornada) {
                return new JsonResponse(['respuesta' => $jornada->getObservaciones(), 'inicio_comida' => $jornada->getInicio_comida(), 'fin_comida' => $jornada->getFin_comida()]);
            }
        }
        return new Response('No tienes acceso.');
    }

    /**
     * @Route("/guardarObservacion", name="guardarObservacion")
     */
    public function guardarObservacion(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $observacion = $request->request->get('observacion');
            $in = $request->request->get('inicio_comida');
            $fin = $request->request->get('fin_comida');

            $entityManager = $this->getDoctrine()->getManager();
            if ($jornada = $entityManager->getRepository(Jornadas::class)->findOneBy(['usuario' => $this->getUser(), 'fecha' => new \DateTime()])) {
                $jornada->setObservaciones($observacion);
                if ($in && $fin) {
                    $jornada->setInicio_comida(new \DateTime($in));
                    $jornada->setFin_comida(new \DateTime($fin));
                }
                $entityManager->persist($jornada);
                $entityManager->flush();
                return new JsonResponse(['respuesta' => 'guardado']);
            }
        }
        return new Response('No tienes acceso.');
    }
    private function comprobar_usuario($codigo)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $usuario = $entityManager->getRepository(Usuario::class)->findOneBy(array('codigo' => $codigo));
        if ($usuario) return $usuario;
        return false;
    }
    private function calcularTiempoNocturnoRecursivo(\DateTime $entrada, \DateTime $salida)
    {
        //solo en jornadas laborables

        $interval = null;
        $seconds1 = 0;
        $seconds2 = 0;
        $inicioNocturno = self::inicioNocturno;
        $finNocturno = self::finNocturno;

        $inicio_rango2 = new \DateTime($entrada->format('Y-m-d') . "{$inicioNocturno}:00:00");
        $fin_rango1 = new \DateTime($entrada->format('Y-m-d') . " {$finNocturno}:00:00");
        $clon = clone $entrada;
        $clon->add(new \DateInterval("P1D"));
        $fin_rango2 = new \DateTime($clon->format('Y-m-d') . ' 00:00:00');

        $entityManager = $this->getDoctrine()->getManager();
        $festivo = $entityManager->getRepository(Festivos::class)->findOneBy(['fecha' => $entrada]);

        //En caso de 00:00 a 06:00
        if (!$festivo && $entrada < $fin_rango1) {
            if ($fin_rango1 < $salida) {
                $interval = $entrada->diff($fin_rango1);
            } else {
                $interval = $entrada->diff($salida);
            }
            $seconds1 = (int)(date_create('@0')->add($interval)->getTimestamp());
        }
        //00:00 < salida
        if ($inicio_rango2 < $salida) {
            if ($entrada < $inicio_rango2) {
                $entrada = $inicio_rango2;
            }
            if ($fin_rango2 < $salida) {
                $interval = $entrada->diff($fin_rango2);
            } else {
                $interval = $entrada->diff($salida);
            }
            if (!$festivo) $seconds2 = (int)(date_create('@0')->add($interval)->getTimestamp());
            if ($fin_rango2 < $salida) {
                return ($seconds1 + $seconds2) + $this->calcularTiempoNocturnoRecursivo($fin_rango2, $salida);
            } else {
                return ($seconds1 + $seconds2);
            }
        } else {
            return ($seconds1 + $seconds2);
        }
    }
    private function calcularTiempoFestivoExtraRecursivo(\DateTime $entrada, \DateTime $salida)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $seconds = 0;
        $festivo = $entityManager->getRepository(Festivos::class)->findOneBy(["fecha" => $entrada]);
        $clon = clone $entrada;
        $clon->add(new \DateInterval("P1D"));
        $tope = new \DateTime($clon->format('Y-m-d') . ' 00:00:00');

        if ($salida < $tope) {
            if ($festivo) $seconds = (int)((date_create('@0')->add($entrada->diff($salida))->getTimestamp()) * self::FESTIVO);
            return $seconds;
        } else {
            if ($festivo) $seconds = (int)((date_create('@0')->add($entrada->diff($tope))->getTimestamp()) * self::FESTIVO);
            return $seconds + $this->calcularTiempoFestivoRecursivo($tope, $salida);
        }
    }
    private function calcularTiempoFestivoRecursivo(\DateTime $entrada, \DateTime $salida)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $seconds = 0;
        $festivo = $entityManager->getRepository(Festivos::class)->findOneBy(["fecha" => $entrada]);
        $clon = clone $entrada;
        $clon->add(new \DateInterval("P1D"));
        $tope = new \DateTime($clon->format('Y-m-d') . ' 00:00:00');

        if ($salida < $tope) {
            if ($festivo) $seconds = (int)((date_create('@0')->add($entrada->diff($salida))->getTimestamp()));
            return $seconds;
        } else {
            if ($festivo) $seconds = (int)((date_create('@0')->add($entrada->diff($tope))->getTimestamp()));
            return $seconds + $this->calcularTiempoFestivoRecursivo($tope, $salida);
        }
    }
    private function permitirEntrada(Jornadas $jornada, Entrada $entrada)
    {
        $entityManager = $this->getDoctrine()->getManager();

        $entradas = $entityManager->getRepository(Entrada::class)->findBy(['cod_jornada' => $jornada->getCodigo()]);
        for ($i = 0; $i < count($entradas); $i++) {
            if (!($entradas[$i]->getSalida() && $entradas[$i]->getSalida() <= $entrada->getEntrada() || ($entrada->getSalida() && $entrada->getSalida() <= $entradas[$i]->getEntrada()))) {
                return false;
            }
        }
        return true;
    }
    private function permitirActualizarEntrada(Entrada $entrada_comprobar, Entrada $entrada)
    {
        if (!($entrada_comprobar->getSalida() && $entrada_comprobar->getSalida() <= $entrada->getEntrada() || ($entrada->getSalida() && $entrada->getSalida() <= $entrada_comprobar->getEntrada()))) {
            return false;
        }
        return true;
    }
}