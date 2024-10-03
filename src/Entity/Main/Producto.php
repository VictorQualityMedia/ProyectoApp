<?php

namespace App\Entity\Main;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;

/**
 * @ORM\Entity @ORM\Table(name="productos")
 */
class Producto
{
    /**
     * @ORM\Id
     * @ORM\Column(type="string")
     */
    private $codigo;
    /**
     * @ORM\Column(type="string")
     */
    private $nombre;
    /**
     * @ORM\Column(type="float")
     */
    private $precio;
    /**
     * @ORM\Column(type="date")
     */
    private $fecha_compra;
    /**
     * @ORM\Column(type="integer")
     */
    private $perdida;
    /** @ORM\Column(type="integer", name = "revision") */
    private $revision = 0;
    /**
     * @ORM\Column(type="integer")
     */
    private $cantidad;
    /**
     * @ORM\OneToOne(targetEntity="Categoria")
     * @ORM\JoinColumn(name="categoria", referencedColumnName="codigo")
     */
    private $categoria;
    /** @ORM\Column(type="json", name = "atributos") */
    private $json;

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

    public function getNombre()
    {
        return $this->nombre;
    }

    public function setNombre($n)
    {
        $this->nombre = $n;
        return $this;
    }

    /**
     * Get the value of precio
     */
    public function getPrecio()
    {
        return $this->precio;
    }

    /**
     * Set the value of precio
     *
     * @return  self
     */
    public function setPrecio($precio)
    {
        $this->precio = $precio;

        return $this;
    }

    /**
     * Get the value of fecha_compra
     */
    public function getFecha_compra()
    {
        return $this->fecha_compra;
    }

    /**
     * Set the value of fecha_compra
     *
     * @return  self
     */
    public function setFecha_compra($fecha_compra)
    {
        $this->fecha_compra = $fecha_compra;

        return $this;
    }

    /**
     * Get the value of perdida
     */
    public function getPerdida()
    {
        return $this->perdida;
    }

    /**
     * Set the value of perdida
     *
     * @return  self
     */
    public function setPerdida($perdida)
    {
        $this->perdida = $perdida;

        return $this;
    }

    public function getCantidad()
    {
        return $this->cantidad;
    }

    public function setCantidad($c)
    {
        $this->cantidad = $c;
        return $this;
    }

    public function getCategoria()
    {
        return $this->categoria;
    }

    public function setCategoria($c)
    {
        $this->categoria = $c;
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
     * Get the value of json
     */ 
    public function getJson()
    {
        return $this->json;
    }

    /**
     * Set the value of json
     *
     * @return  self
     */ 
    public function setJson($json)
    {
        $this->json = $json;

        return $this;
    }
}
