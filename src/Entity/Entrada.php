<?php
namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
/**
 * @ORM\Entity @ORM\Table(name="entradas")
 */
class Entrada {
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $codigo;
    /**
     * @ORM\Column(type="integer")
     */
    private $cod_jornada;
    /**
     * @ORM\Column(type="datetimetz")
     */
    private $entrada;
    /**
     * @ORM\Column(type="datetimetz")
     */
    private $salida;
    /**
     * @ORM\Column(type="integer")
     */
    private $recuperar;
   

    //codigo jornada
    public function getCodigo() {
        return $this->codigo;
    }
    public function getJornada() {
        return $this->cod_jornada;
    }
    public function setJornada($cod_jornada) {
        $this->cod_jornada = $cod_jornada;
    }
    //hora
    public function getEntrada() {
        return $this->entrada;
    }
    public function setEntrada($entrada) {
        $this->entrada = $entrada;
    }
    //salida
    public function getSalida() {
        return $this->salida;
    }
    public function setSalida($salida) {
        $this->salida = $salida;
    }

    /**
     * Get the value of recuperar
     */ 
    public function getRecuperar()
    {
        return $this->recuperar;
    }

    /**
     * Set the value of recuperar
     *
     * @return  self
     */ 
    public function setRecuperar($recuperar)
    {
        $this->recuperar = $recuperar;

        return $this;
    }
}
?>