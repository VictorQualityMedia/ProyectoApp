<?php
namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
/**
 * @ORM\Entity @ORM\Table(name="tipos_jornada")
 */
class Tipo_jornada {
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $cod_tipo;
    /**
     * @ORM\Column(type="string")
     */
    private $nombre;
    
    //tipo codigo
    public function getTipo() {
        return $this->cod_tipo;
    }
    
    public function setTipo($cod_tipo) {
        $this->cod_tipo = $cod_tipo;
    }
    //nombre
    public function getNombre() {
        return $this->nombre;
    }
   
    public function setNombre($nombre) {
        $this->nombre = $nombre;
    }
}
?>