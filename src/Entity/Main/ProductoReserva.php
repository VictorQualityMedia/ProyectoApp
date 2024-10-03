<?php

namespace App\Entity\Main;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\JoinColumn;

/**
 * @ORM\Entity @ORM\Table(name="productos_reserva")
 */
class ProductoReserva
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $codigo;

    /**
     * @ORM\Column(type="integer")
     */
    private $reserva;
    /**
     * @ORM\Column(type="string")
     */
    private $producto;

    /**
     * @ORM\OneToOne(targetEntity="Producto")
     * @ORM\JoinColumn(name="producto", referencedColumnName="codigo")
     */
    private $producto_objeto;

    /**
     * @ORM\Column(type="integer")
     */
    private $cantidad;

    /**
     * @ORM\Column(type="string")
     */
    private $observacion;

    /**
     * Get the value of cantidad
     */ 
    public function getCantidad()
    {
        return $this->cantidad;
    }

    /**
     * Set the value of cantidad
     *
     * @return  self
     */ 
    public function setCantidad($cantidad)
    {
        $this->cantidad = $cantidad;

        return $this;
    }

    /**
     * Get the value of etiqueta
     */ 
    public function getProducto()
    {
        return $this->producto;
    }

    /**
     * Set the value of etiqueta
     *
     * @return  self
     */ 
    public function setProducto($prod)
    {
        $this->producto = $prod;

        return $this;
    }

    public function getProductoObjeto() {
        return $this->producto_objeto;
    }

    public function setProductoObjeto($p) {
        $this->producto_objeto = $p;
        return $this;
    }


    /**
     * Get the value of reserva
     */ 
    public function getReserva()
    {
        return $this->reserva;
    }

    /**
     * Set the value of reserva
     *
     * @return  self
     */ 
    public function setReserva($reserva)
    {
        $this->reserva = $reserva;

        return $this;
    }

    /**
     * Get the value of codigo
     */ 
    public function getCodigo()
    {
        return $this->codigo;
    }

    /**
     * Set the value of codigo
     *
     * @return  self
     */ 
    public function setCodigo($codigo)
    {
        $this->codigo = $codigo;

        return $this;
    }

    /**
     * Get the value of observacion
     */ 
    public function getObservacion()
    {
        return $this->observacion;
    }

    /**
     * Set the value of observacion
     *
     * @return  self
     */ 
    public function setObservacion($observacion)
    {
        $this->observacion = $observacion;

        return $this;
    }
}
