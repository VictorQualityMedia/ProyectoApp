<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity @ORM\Table(name="jornadas")
 */
class Jornadas
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $codigo;
    /**
     * @ORM\Column(type="string")
     */
    private $observaciones;
    /**
     * @ORM\Column(type="integer")
     */
    private $confirmado;
    /**
     * @ORM\Column(type="date")
     */
    private $fecha;
    /**
     * @ORM\Column(type="integer")
     */
    private $comida;
    /**
     * @ORM\Column(type="datetimetz")
     */
    private $inicio_comida;
    /**
     * @ORM\Column(type="datetimetz")
     */
    private $fin_comida;
    /**
     * @ORM\Column(type="integer")
     */
    private $tipo_jornada;
    /**
     * @ORM\ManyToOne(targetEntity="Usuario")
     * @ORM\JoinColumn(name="cod_usuario", referencedColumnName="codigo")
     */
    private $usuario;
    /**
     * @ORM\ManyToOne(targetEntity="Tipo_jornada")
     * @ORM\JoinColumn(name="tipo_jornada", referencedColumnName="cod_tipo")
     */
    private $jornada;
    /**
     * @ORM\Column(type="integer", name="festivo")
     */
    private $festivo;
    /**
     * @ORM\Column(type="string", name="zona")
     */
    private $zona = 'Europe/Madrid';

    //codigo
    public function getCodigo()
    {
        return $this->codigo;
    }
    public function setCodigo($codigo)
    {
        $this->codigo = $codigo;
    }
    //observaciones
    public function getObservaciones()
    {
        return $this->observaciones;
    }
    public function setObservaciones($observaciones)
    {
        $this->observaciones = $observaciones;
    }
    //confirmado
    public function getConfirmado()
    {
        return $this->confirmado;
    }
    public function setConfirmado($confirmado)
    {
        $this->confirmado = $confirmado;
    }
    //fecha
    public function getFecha()
    {
        return $this->fecha;
    }
    public function setFecha($fecha)
    {
        $this->fecha = $fecha;
    }
    //Cod_usuario
    public function getUsuario()
    {
        return $this->usuario;
    }
    public function setUsuario($usuario)
    {
        $this->usuario = $usuario;
    }
    //tipo_jornada
    public function getJornada()
    {
        return $this->jornada;
    }
    public function setJornada($jornada)
    {
        $this->jornada = $jornada;
    }

    public function getFestivo()
    {
        return $this->festivo;
    }

    public function setFestivo($festivo)
    {
        $this->festivo = $festivo;
    }

    public function getComida()
    {
        return $this->comida;
    }
    public function setComida($comida)
    {
        $this->comida = $comida;
    }

    /**
     * Get the value of zona
     */
    public function getZona()
    {
        return $this->zona;
    }

    /**
     * Set the value of zona
     *
     * @return  self
     */
    public function setZona($zona)
    {
        $this->zona = $zona;

        return $this;
    }

    /**
     * Get the value of tipo_jornada
     */
    public function getTipo_jornada()
    {
        return $this->tipo_jornada;
    }

    /**
     * Set the value of tipo_jornada
     *
     * @return  self
     */
    public function setTipo_jornada($tipo_jornada)
    {
        $this->tipo_jornada = $tipo_jornada;

        return $this;
    }

    /**
     * Get the value of inicio_comida
     */ 
    public function getInicio_comida()
    {
        return $this->inicio_comida;
    }

    /**
     * Set the value of inicio_comida
     *
     * @return  self
     */ 
    public function setInicio_comida($inicio_comida)
    {
        $this->inicio_comida = $inicio_comida;

        return $this;
    }

    /**
     * Get the value of fin_comida
     */ 
    public function getFin_comida()
    {
        return $this->fin_comida;
    }

    /**
     * Set the value of fin_comida
     *
     * @return  self
     */ 
    public function setFin_comida($fin_comida)
    {
        $this->fin_comida = $fin_comida;

        return $this;
    }
}
