<?php

namespace App\Controller;

use App\Entity\Main\Categoria;
use App\Entity\Main\Prestamo;
use App\Entity\Main\Producto;
use App\Entity\Main\Etiqueta;
use App\Entity\Fichar\Usuario as FicharUsuario;
use App\Entity\Main\Administrador;
use App\Entity\Main\ProductoReserva;
use App\Entity\Main\Reserva;
use Proxies\__CG__\App\Entity\Fichar\Usuario;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ProductoController extends AbstractController
{

    private function productosDisponibles($codigo)
    {
        $entityManager = $this->getDoctrine()->getManager("default");
        $producto = $entityManager->getRepository(Producto::class)->findOneBy(['codigo' => $codigo]);
        $disponibles = $producto->getCantidad() - $producto->getPerdida() - $producto->getRevision();

        $prestamos = $entityManager->getRepository(Prestamo::class)->findBy(['producto' => $producto->getCodigo(), "devolucion" => null]);
        foreach ($prestamos as $prestamo) {
            $disponibles -= $entityManager->getRepository(ProductoReserva::class)->findOneBy(['producto' => $prestamo->getProducto(), "reserva" => $prestamo->getReserva()])->getCantidad();
        }
        return $disponibles;
    }
    /**
     * @Route("/modificar_producto", name="modificar_producto")
     */
    public function modificarProducto(Request $request)
    {
        $productos = $request->request->get('productos');
        $entityManager = $this->getDoctrine()->getManager("default");
        $json = null;
        /** @var array $productos  */
        foreach ($productos as $p) {
            $codigo = $p['codigo'];
            $nombre = $p['nombre'];
            $precio = $p['precio'];
            $fecha = new \DateTime($p['fecha']);
            $cantidad = $p['cantidad'];
            $perdida = $p['perdida'];
            $revision = $p['revision'];
            $categoria = $p['categoria'];
            $json = $p['atributos'];
            $json = $json ? $json : "{}";
            $eliminar =  $p['eliminar'];

            if (!$entityManager->getRepository(Administrador::class)->findOneBy(['usuario' => $this->getUser()->getCodigo()])) return new JsonResponse(['estado' => false]);
            $producto = $entityManager->getRepository(Producto::class)->findOneBy(['codigo' => $codigo]);
            if ($eliminar) {
                $entityManager->remove($producto);
            } else {
                $producto = $producto ? $producto : new Producto();
                $producto->setCodigo($codigo);
                $producto->setNombre($nombre ? $nombre : $codigo);
                $producto->setPrecio($precio);
                $producto->setFecha_compra($fecha);
                $producto->setCantidad($cantidad);
                $producto->setRevision($revision);
                $producto->setPerdida($perdida);
                $producto->setJson($json);
                $categoria = ucfirst(strtolower(trim($categoria)));
                $cat = $entityManager->getRepository(Categoria::class)->findOneBy(['nombre' => trim($categoria)]);
                if ($cat) {
                    $producto->setCategoria($cat);
                } else if ($categoria) {
                    $cat = new Categoria();
                    $cat->setNombre($categoria);
                    $entityManager->persist($cat);
                    $entityManager->flush();
                    $producto->setCategoria($cat);
                }
                $entityManager->persist($producto);
            }


            $entityManager->flush();
        }

        return new JsonResponse(['estado' => true,'json' => $json]);
    }
    /**
     * @Route("/devolver_producto", name="devolver_producto")
     */
    public function devolver_producto(Request $request)
    {
        $js['aceptado'] = false;
        $entityManager = $this->getDoctrine()->getManager("default");
        $reserva = $request->request->get('reserva');
        $productos = (array) $request->request->get('productos');
        foreach ($productos as $producto) {
            $prestamo = $entityManager->getRepository(Prestamo::class)->findOneBy(['reserva' => $reserva, 'producto' => $producto[0]]);
            if ($prestamo) {
                $prestamo->setObservacion($producto[2]);
                $prestamo->setReceptor($this->getUser()->getCodigo());
                $prestamo->setEstado($producto[1]);
                $prestamo->setRevision($producto[1] ? $producto[3] : 0);
                $prestamo->getProducto_objeto()->setRevision($prestamo->getProducto_objeto()->getRevision() + $prestamo->getRevision());
                $prestamo->setDevolucion(new \DateTime());
                $js['aceptado'] = true;
            }
        }
        $entityManager->flush();
        return new JsonResponse($js);
    }

    /**
     * @Route("/borrarReservas", name="borrar_reservas")
     */
    public function borrarReservas(Request $request)
    {
        $em = $this->getDoctrine()->getManager("default");
        $cod_productos = $request->request->get("productos");

        foreach ($cod_productos as $cod_producto) {
            $cantidad = $this->productosDisponibles($cod_producto);
            $productos = $em->getRepository(ProductoReserva::class)->findBy(['producto' => $cod_producto]);

            foreach ($productos as $p) {
                $cod_reserva = $p->getReserva();
                $reserva = $em->getRepository(Reserva::class)->findOneBy(['codigo' => $cod_reserva]);
                if (!is_numeric($reserva->getDenegado()) && $cantidad == 0) {
                    $em->remove($reserva);
                    $em->flush();
                }
            }
        }

        return new JsonResponse(['borrado' => true]);
    }

    /**
     * @Route("/categorias", name="cargar_categorias")
     */
    public function cargarCategorias()
    {
        $entityManager = $this->getDoctrine()->getManager("default");
        $categorias = $entityManager->getRepository(Categoria::class)->findAll();
        $json = [];
        $i = 0;
        foreach ($categorias as $c) {
            $json[$i]['codigo'] = $c->getCodigo();
            $json[$i]['nombre'] = $c->getNombre();
            $i++;
        }
        return new JsonResponse($json);
    }
    /**
     * @Route("/traer_reserva", name="traer_reserva")
     */
    public function traerReserva(Request $request)
    {
        $js = [];
        $correo = $request->request->get('usuario');
        $entityManager = $this->getDoctrine()->getManager("default");
        $usuario =  $this->getDoctrine()->getManager()->getRepository(Usuario::class)->findOneBy(['correo' => $correo]);
        if ($usuario) {
            $reservas = $entityManager->getRepository(Reserva::class)->findBy(['usuario' => $usuario->getCodigo(), "denegado" => 0]);
            foreach ($reservas as $reserva) {
                if ($entityManager->getRepository(Prestamo::class)->findOneBy(['reserva' => $reserva->getCodigo(), "devolucion" => null])) {
                    $j = [];
                    $j['codigo'] = $reserva->getCodigo();
                    $j['peticion'] = $reserva->getPeticion();
                    $js[] = $j;
                }
            }
        }
        return new JsonResponse($js);
    }
    /**
     * @Route("/buscar_reserva", name="buscar_reserva")
     */
    public function buscarReserva(Request $request)
    {
        $js = [];
        $reserva = $request->request->get('reserva');
        $entityManager = $this->getDoctrine()->getManager("default");
        $reserva = $entityManager->getRepository(Reserva::class)->findOneBy(['codigo' => $reserva]);
        if ($reserva) {
            $prestamos = $entityManager->getRepository(Prestamo::class)->findBy(['reserva' => $reserva->getCodigo()]);
            foreach ($prestamos as $prestamo) {
                $j = [];
                $producto = $entityManager->getRepository(ProductoReserva::class)->findOneBy(['reserva' => $prestamo->getReserva(), "producto" => $prestamo->getProducto()]);
                $j['codigo'] = $producto->getProducto();
                $j['nombre'] = $producto->getProductoObjeto()->getNombre();
                $j['cantidad'] = $producto->getCantidad();
                $js[] = $j;
            }
        }
        return new JsonResponse($js);
    }
    /**
     * @Route("/buscar_producto", name="buscar_producto")
     */
    public function buscarProducto(Request $request)
    {
        $js = [];
        $producto = $request->request->get('etiqueta');
        $entityManager = $this->getDoctrine()->getManager("default");
        $js['codigo'] = $producto;
        $producto = $entityManager->getRepository(Producto::class)->findOneBy(['codigo' => $producto]);
        if ($producto) {
            $js['registrado'] = true;
            $js['nombre'] = $producto->getNombre();
            $js['precio'] = $producto->getPrecio();
            $js['fecha'] = $producto->getFecha_compra();
            $js['cantidad'] = $producto->getCantidad();
            $js['disponible'] = $this->productosDisponibles($producto->getCodigo());
            $js['perdida'] = $producto->getPerdida();
            $js['revision'] = $producto->getRevision();
            $js['atributos'] = $producto->getJson() ? $producto->getJson() : '{}';
            $js['categoria'] = $producto->getCategoria() ? $producto->getCategoria()->getNombre() : "";
        } else {
            $js['registrado'] = false;
        }
        return new JsonResponse($js);
    }

    /**
     * @Route("/cargarProductos", name="cargar_productos")
     */
    public function cargarProductos(Request $request)
    {
        $entityManager = $this->getDoctrine()->getManager("default");
        $session = $request->getSession();
        $categoria = $request->request->get('categoria');
        $nombre = $request->request->get('nombre');

        // Verificar si lo ha escaneado por codigo 

        $producto = $entityManager->getRepository(Producto::class)->findOneBy(['codigo' => $nombre]);

        if ($producto) {
            $carrito = $session->get('carrito');
            if (!$carrito) {
                $session->set('carrito', [['id' => $nombre, 'nombre' => $producto->getNombre(), 'unidades' => 1, 'cantidad' => $this->productosDisponibles($producto->getCodigo())]]);
            } else {
                foreach ($carrito as $i => $e) {
                    if ($e['id'] == $nombre) {
                        unset($carrito[$i]);
                        $carrito[] = ['id' => $nombre, 'nombre' => $producto->getNombre(), 'unidades' => $e['unidades'] + 1, 'cantidad' => $this->productosDisponibles($producto->getCodigo())];
                        $session->set('carrito', $carrito);
                    }
                }
            }
            return new JsonResponse(['respuesta' => "Producto añadido al carrito"]);
        } else {
            // Todos los productos
            if ($categoria == 0) {
                $qb = $entityManager->getRepository(Producto::class)->createQueryBuilder('e')->andWhere("e.nombre LIKE '%{$nombre}%'");
            } else {
                // Filtrar por categoria
                $qb = $entityManager->getRepository(Producto::class)->createQueryBuilder('e')->andWhere("e.nombre LIKE '%{$nombre}%'")->andWhere("e.categoria = '{$categoria}'");
            }
            $productos = $qb->getQuery()->getResult();
            $json = [];
            $i = 0;

            foreach ($productos as $p) {
                if ($this->productosDisponibles($p->getCodigo()) > 0) {
                    $json[$i]['id'] = $p->getCodigo();
                    $json[$i]['nombre'] = $p->getNombre();
                    $json[$i]['stock'] = $this->productosDisponibles($p->getCodigo());
                    $i++;
                }
            }
            return new JsonResponse($json);
        }
    }
    /**
     * @Route("/imprimirProductos", name="imprimir_productos")
     */
    public function imprimirProducto()
    {
        $js = [];
        $entityManager = $this->getDoctrine()->getManager('default');
        $productos = $entityManager->getRepository(Producto::class)->findAll();
        foreach ($productos as $producto) {
            $product = [];
            $product['codigo'] = $producto->getCodigo();
            $product['nombre'] = $producto->getNombre();
            $product['precio'] = $producto->getPrecio();
            $product['fecha'] = $producto->getFecha_compra();
            $product['cantidad'] = $producto->getCantidad();
            $product['perdida'] = $producto->getPerdida();
            $product['categoria'] = $producto->getCategoria() ? $producto->getCategoria()->getNombre() : "";
            $js[] = $product;
        }
        return new JsonResponse($js);
    }

    /**
     * @Route("/guardarCarrito", name="guardar_carrito")
     */
    public function guardarProductoCarrito(Request $request)
    {
        $session = $request->getSession();
        $id = $request->request->get('id');
        $ud = $request->request->get('cantidad');
        $entityManager = $this->getDoctrine()->getManager("default");

        $producto = $entityManager->getRepository(Producto::class)->findOneBy(['codigo' => $id]);

        $carrito = $session->get('carrito');

        if (!$carrito) {
            $session->set('carrito', [['id' => $id, 'nombre' => $producto->getNombre(), 'unidades' => $ud, 'cantidad' => $this->productosDisponibles($producto->getCodigo())]]);
        } else {
            $elemento_encontrado = false;

            foreach ($carrito as $e) {
                if ($e['id'] == $id) {
                    $elemento_encontrado = true;
                }
            }

            if (!$elemento_encontrado) {
                $carrito[] = ['id' => $id, 'nombre' => $producto->getNombre(), 'unidades' => $ud, 'cantidad' => $this->productosDisponibles($producto->getCodigo())];
                $session->set('carrito', $carrito);
            }
        }
        return new JsonResponse(['respuesta' => "Producto añadido al carrito"]);
    }
    /**
     * @Route("/eliminar_producto_reserva", name="eliminar_producto_reserva")
     */
    public function eliminar_producto_reserva(Request $request)
    {
        $session = $request->getSession();
        $id = $request->request->get('producto');
        $carrito = $session->get('carrito');

        for ($i = 0; $i < count($carrito); $i++) {
            if ($carrito[$i]['id'] == $id) {
                array_splice($carrito, $i--, 1);
                $session->set('carrito', $carrito);
                break;
            }
        }
        return new JsonResponse(['respuesta' => $carrito]);
    }

    /**
     * @Route("/reservar", name="reservar_productos")
     */
    public function reservarProductos(Request $request)
    {
        /** @var array */
        $productos = $request->request->get('productos') ? $request->request->get('productos') : [];
        $entityManager = $this->getDoctrine()->getManager("default");

        $reserva = new Reserva();
        $reserva->setUsuario($this->getUser()->getCodigo());
        $reserva->setFecha(new \DateTime());
        $reserva->setPeticion(new \DateTime($request->request->get('peticion')));
        $creado = false;

        foreach ($productos as $p) {
            if ($p['cantidad'] > 0) {
                if (!$creado) {
                    $creado = true;
                    $reserva = new Reserva();
                    $reserva->setUsuario($this->getUser()->getCodigo());
                    $reserva->setFecha(new \DateTime());
                    $reserva->setPeticion(new \DateTime($request->request->get('peticion')));
                    $entityManager->persist($reserva);
                    $entityManager->flush();
                }
                $p_reserva = new ProductoReserva();
                $producto = $entityManager->getRepository(Producto::class)->findOneBy(['codigo' => ($p['id'])]);
                $p_reserva->setReserva($reserva->getCodigo());
                $p_reserva->setProductoObjeto($producto);
                $p_reserva->setCantidad($p['cantidad']);
                $entityManager->persist($p_reserva);
                $entityManager->flush();
            }
        }

        $request->getSession()->remove('carrito');

        return new JsonResponse(['completado' => true]);
    }

    /**
     * @Route("/cargarPrestamos", name="cargar_prestamos")
     */
    public function cargarPrestamos()
    {
        $entityManager = $this->getDoctrine()->getManager("default");

        $id = $this->getUser()->getCodigo();
        $es_admin = $entityManager->getRepository(Administrador::class)->findOneBy(['usuario' => $id]);
        if (!$es_admin) {
            $json = [];

            $reservas = $entityManager->getRepository(Reserva::class)->findBy(['usuario' => $id, 'denegado' => 0]);

            foreach ($reservas as $i => $r) {
                $cod_reserva = $r->getCodigo();

                $prestamo = $entityManager->getRepository(Prestamo::class)->findOneBy(['reserva' => $cod_reserva]);

                $em2 = $this->getDoctrine()->getManager();
                $receptor = $em2->getRepository(FicharUsuario::class)->findOneBy(['codigo' => $prestamo->getReceptor()]);
                if ($receptor != null) {
                    $receptor = $receptor->getNombre();
                } else {
                    $receptor = 'NO TIENE';
                }


                $json[$i] = ["id" => $cod_reserva, "fecha" => $r->getFecha(), "solicitud" => $r->getPeticion(), "productos" => [], "observacion" => $prestamo->getObservacion(), "receptor" => $receptor, "estado" => $prestamo->getEstado(), "devolucion" => $prestamo->getDevolucion()];

                $productos_reserva = $entityManager->getRepository(ProductoReserva::class)->findBy(['reserva' => $cod_reserva]);
                foreach ($productos_reserva as $p) {
                    $prestamo = $entityManager->getRepository(Prestamo::class)->findOneBy(['reserva' => $cod_reserva, 'producto' => $p->getProducto()]);
                    $obs_f = $prestamo->getObservacion();
                    $cantidad = $p->getCantidad();
                    $obs = $p->getObservacion();
                    $producto = $entityManager->getRepository(Producto::class)->findOneBy(['codigo' => $p->getProducto()]);
                    array_push($json[$i]['productos'], ["nombre" => $producto->getNombre(), "cantidad" => $cantidad, "observaciones_i" => $obs, "observaciones_f" => $obs_f]);
                }
            }

            return new JsonResponse($json);
        } else {
            return $this->cargarTodosPrestamos();
        }
    }

    public function cargarTodosPrestamos()
    {
        $entityManager = $this->getDoctrine()->getManager("default");

        $reservas = $entityManager->getRepository(Reserva::class)->findBy(['denegado' => 0]);
        $json = [];
        foreach ($reservas as $i => $r) {
            $cod_reserva = $r->getCodigo();
            $em2 = $this->getDoctrine()->getManager();
            $usu = $em2->getRepository(FicharUsuario::class)->findOneBy(['codigo' => $r->getUsuario()]);

            $prestamo = $entityManager->getRepository(Prestamo::class)->findOneBy(['reserva' => $cod_reserva]);

            $receptor = $em2->getRepository(FicharUsuario::class)->findOneBy(['codigo' => $prestamo->getReceptor()]);
            if ($receptor != null) {
                $receptor = $receptor->getNombre();
            } else {
                $receptor = 'NO TIENE';
            }

            $json[$i] = ["id" => $cod_reserva, "usuario" => $usu->getNombre(),  "fecha" => $r->getFecha(), "solicitud" => $r->getPeticion(), "productos" => [], "observacion" => $prestamo->getObservacion(), "receptor" => $receptor, "estado" => $prestamo->getEstado(), "devolucion" => $prestamo->getDevolucion()];

            $productos_reserva = $entityManager->getRepository(ProductoReserva::class)->findBy(['reserva' => $cod_reserva]);
            foreach ($productos_reserva as $p) {
                $prestamo = $entityManager->getRepository(Prestamo::class)->findOneBy(['reserva' => $cod_reserva, 'producto' => $p->getProducto()]);
                $obs_f = $prestamo->getObservacion();
                $cantidad = $p->getCantidad();
                $obs = $p->getObservacion();
                $producto = $entityManager->getRepository(Producto::class)->findOneBy(['codigo' => $p->getProducto()]);
                array_push($json[$i]['productos'], ["nombre" => $producto->getNombre(), "cantidad" => $cantidad, "observaciones_i" => $obs, "observaciones_f" => $obs_f]);
            }
        }

        return new JsonResponse($json);
    }

    /**
     * @Route("/porConfirmar", name="cargar_por_confirmar")
     */
    public function cargarReservasPorConfirmar()
    {
        $entityManager = $this->getDoctrine()->getManager("default");

        $reservas = $entityManager->getRepository(Reserva::class)->findBy(['denegado' => null]);
        $json = [];
        foreach ($reservas as $i => $r) {
            $cod_reserva = $r->getCodigo();
            $em2 = $this->getDoctrine()->getManager();
            $usu = $em2->getRepository(FicharUsuario::class)->findOneBy(['codigo' => $r->getUsuario()]);
            $json[$i] = ["id" => $cod_reserva, "usuario" => $usu->getNombre(), "fecha" => $r->getFecha(), "solicitud" => $r->getPeticion(), "productos" => []];

            $productos_reserva = $entityManager->getRepository(ProductoReserva::class)->findBy(['reserva' => $cod_reserva]);
            foreach ($productos_reserva as $p) {
                $cantidad = $p->getCantidad();
                $producto = $entityManager->getRepository(Producto::class)->findOneBy(['codigo' => $p->getProducto()]);
                array_push($json[$i]['productos'], ["id_producto" => $p->getProducto(), "nombre" => $producto->getNombre(), "cantidad" => $cantidad]);
            }
        }

        return new JsonResponse($json);
    }

    /**
     * @Route("/cargarCarrito", name="cargar_carrito")
     */
    public function cargarCarrito(Request $request)
    {
        $session = $request->getSession();
        // $session->set('carrito', null);

        return new JsonResponse($session->get('carrito'));
    }

    /**
     * @Route("/confirmarSol", name="confirmar_solicitud")
     */
    public function confirmarSolicitud(Request $request)
    {
        $a_confirmar = $request->request->get('confirmar');
        $id = $request->request->get('id');
        $comentarios = $request->request->get('comentarios');
        $em = $this->getDoctrine()->getManager("default");
        $reserva = $em->getRepository(Reserva::class)->findOneBy(['codigo' => $id]);
        if ($a_confirmar == "true") {
            $reserva->setDenegado(0);
            $productos = $em->getRepository(ProductoReserva::class)->findBy(['reserva' => $reserva->getCodigo()]);
            foreach ($productos as $i => $producto) {
                $producto->setObservacion($comentarios[$i]);
                $prestamo = new Prestamo();
                $prestamo->setReserva_object($reserva);
                $prestamo->setProducto_objeto($producto->getProductoObjeto());
                $em->persist($prestamo);
            }
        } else {
            $reserva->setDenegado(1);
        }

        $em->flush();


        return new JsonResponse(['exito' => true]);
    }
}
