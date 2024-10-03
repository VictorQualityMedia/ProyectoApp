<?php

namespace App\Entity\Main;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\JoinColumn;

/**
 * @ORM\Entity @ORM\Table(name="prestamos")
 */
class Prestamo
{
    /**
     * @ORM\Id 
     * @ORM\GeneratedValue 
     * @ORM\Column(type="integer", name = "codigo")
     */
    private $codigo;
    /**
     *  @ORM\Column(type="integer", name = "reserva") */
    private $reserva;
    /** 
     *  @ORM\Column(type="string", name = "producto") */
    private $producto;
    /** @ORM\Column(type="string", name = "observacion") */
    private $observacion;
    /** @ORM\Column(type="integer") */
    private $receptor;
    /** @ORM\Column(type="integer", name = "estado") */
    private $estado;
    /** @ORM\Column(type="integer", name = "revision") */
    private $revision = 0;
    /** @ORM\Column(type="date") */
    private $devolucion;
    /**
     * @ORM\OneToOne(targetEntity="Reserva")
     * @ORM\JoinColumn(name="reserva", referencedColumnName="codigo")
     */
    private $reserva_object;
    /**
     * @ORM\OneToOne(targetEntity="Producto")
     * @ORM\JoinColumn(name="producto", referencedColumnName="codigo")
     */
    private $producto_objeto;

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
     * Get the value of producto
     */
    public function getProducto()
    {
        return $this->producto;
    }

    /**
     * Set the value of producto
     *
     * @return  self
     */
    public function setProducto($producto)
    {
        $this->producto = $producto;

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

    /**
     * Get the value of receptor
     */
    public function getReceptor()
    {
        return $this->receptor;
    }

    /**
     * Set the value of receptor
     *
     * @return  self
     */
    public function setReceptor($receptor)
    {
        $this->receptor = $receptor;

        return $this;
    }

    /**
     * Get the value of estado
     */
    public function getEstado()
    {
        return $this->estado;
    }

    /**
     * Set the value of estado
     *
     * @return  self
     */
    public function setEstado($estado)
    {
        $this->estado = $estado;

        return $this;
    }

    /**
     * Get the value of devolucion
     */
    public function getDevolucion()
    {
        return $this->devolucion;
    }

    /**
     * Set the value of devolucion
     *
     * @return  self
     */
    public function setDevolucion($devolucion)
    {
        $this->devolucion = $devolucion;

        return $this;
    }

    /**
     * Get the value of reserva_object
     */
    public function getReserva_object()
    {
        return $this->reserva_object;
    }

    /**
     * Set the value of reserva_object
     *
     * @return  self
     */
    public function setReserva_object($reserva_object)
    {
        $this->reserva_object = $reserva_object;

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
     * Get the value of revision
     */
    public function getRevision()
    {
        return $this->revision;
    }

    /**
     * Set the value of revision
     *
     * @return  self
     */
    public function setRevision($revision)
    {
        $this->revision = $revision;

        return $this;
    }

    /**
     * Get the value of producto_objeto
     */ 
    public function getProducto_objeto()
    {
        return $this->producto_objeto;
    }

    /**
     * Set the value of producto_objeto
     *
     * @return  self
     */ 
    public function setProducto_objeto($producto_objeto)
    {
        $this->producto_objeto = $producto_objeto;

        return $this;
    }
}
