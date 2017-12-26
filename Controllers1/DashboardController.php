<?php

/**
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */
namespace Application\Controller;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Application\Entity\Recurso;
use Application\Entity\Personal;
use Application\Entity\Anuncios;
use Zend\Session\Container;
use Application\Entity\UsuarioRol;
use Application\Entity\RecursoRol;
use Doctrine\ORM\Query\ResultSetMapping;
class DashboardController extends AbstractActionController {
	/**
	 * Entity manager.
	 * 
	 * @var Doctrine\ORM\EntityManager
	 */
	private $entityManager;
	private $entityManager2;
	public function __construct($entityManager, $entityManager2) {
		$this->entityManager = $entityManager;
		$this->entityManager2 = $entityManager2;
	}
	public function dashboardAction() {
	
		$idtab = 1588;
		// Obtengo el rol del usuario apartir de la session
		$usuarioAD = $this->identity ();
		$rolusuario = $this->entityManager->getRepository ( UsuarioRol::class )->findOneByidUsuario ( $usuarioAD );
		$rol = $rolusuario->getIdRol ();
		$verificarol = $this->entityManager->getRepository ( RecursoRol::class )->findBy ( array ('idRol' => $rol,'idRecurso' => $idtab	) );
		$descargar = $verificarol [0]->getDescargar ();
		$editar = $verificarol [0]->getEditar ();
		$eliminar = $verificarol [0]->getEliminar ();
		$personal = $this->entityManager2->getRepository ( Personal::class )->findOneByUsuarioActiveDirectory ( $this->identity () );
		$sessionContainer = new Container ( 'ContainerPersonal', $sessionManager );
		$sessionContainer->nombre = $personal->getNombre ();
		$sessionContainer->apellido = $personal->getApellido ();
		$sessionContainer->imagen = $personal->getImagen ();
		$sessionContainer->cargo = $personal->getCargo ();
		$sessionContainer->bu = $personal->getBU ();
		$sessionContainer->rol = $rol;
		$anuncio = $this->entityManager->getRepository ( Anuncios::class )->findBy ( array (), array ('idanuncio' => 'DESC'), 3 );
		if (is_array ( $anuncio )) {
			$anuncios = $anuncio;
		} else
			$anuncios [0] = $anuncio;
		$imagennodisponible = $this->entityManager->getRepository ( Recurso::class )->findBy ( array ('idRecursoPadre' => 1588), array ('FechaRecurso' => 'DESC'), 4 );
		if (is_array ( $imagenesnodisponible )) {
			$imagenesnodisponible = $imagennodisponible;
		} else
			$imagenesnodisponible [0] = $imagennodisponible;
		$imagen = $this->entityManager->getRepository ( Recurso::class )->findBy ( array ('idRecursoPadre' => 1588), array ('FechaRecurso' => 'DESC'), 4 );
		if (is_array ( $imagenes )) {
			$imagenes = $imagen;
		} else
			$imagenes [0] = $imagen;
		if ($imagen [0] == NULL) {
			$imagen [0] = $imagennodisponible [0];
		}
		if ($imagen [1] == NULL) {
			$imagen [1] = $imagennodisponible [0];
		}
		if ($imagen [2] == NULL) {
			$imagen [2] = $imagennodisponible [0];
		}
		if ($imagen [3] == NULL) {
			$imagen [3] = $imagennodisponible [0];
		}
		$idgaleria = 1589;
		$ultimoalbum = $this->entityManager->getRepository ( Recurso::class )->findBy ( array ('idRecursoPadre' => $idgaleria 
		), array ('FechaRecurso' => DESC 
		), 1 );
		$idultimafoto = $ultimoalbum [0]->getIdRecurso ();
		$ruta = 'public/upload/galeria/galeria_fotos/' . $ultimoalbum [0];
		$rutalink = $this->CrearRuta ( $idultimafoto );
		$foto = scandir ( $ruta );

		
		unset ( $foto [0], $foto [1] );
		 $ultimasfotos = array ();
		// $fotosrandom = array_rand($foto,5);
		// array_push ( $ultimasfotos,$foto[$fotosrandom[0]] );
		// array_push ( $ultimasfotos,$foto[$fotosrandom[2]] );
		// array_push ( $ultimasfotos,$foto[$fotosrandom[3]] );
		// array_push ($ultimasfotos,$foto[$fotosrandom[4]] );
		$ultimasfotos = array_slice($foto,-4,4,true);
		
		if ($personal == null) {
			throw new \Exception ( 'No se encuentra usuario con ese email' );
		}
		return new ViewModel ( [ 'personal' => $personal,'anuncio' => $anuncio,'imagen' => $imagen,'descargar' => $descargar,'editar' => $editar,'eliminar' => $eliminar,'rutalink' => $rutalink,'ultimasfotos' => $ultimasfotos] );
	}
	public function PoblarNovedadesAction() {

		date_default_timezone_set("America/Lima");
		$recurso = $this->entityManager->getRepository ( Recurso::class )->findBy ( array ('TipoRecurso' => 'A','UrlRecurso' => NULL), array ('FechaRecurso' => 'DESC'), 8 );

		
		if (is_array ( $recurso )) {
			$recursos = $recurso;
		} else
			$recursos [0] = $recurso;
		$cadena = "";
		foreach ( $recursos as $recurso ) {
			$nombre = $recurso->getDescripcionRecurso ();
			if (is_numeric ( $nombre )) {
				if ($nombre > 0 && $nombre < 13) {
					$mes = $this->ConvertirAMes ( $nombre );
					$IdPadreIdPadre  = $this->getIdPadreIdPadre($recurso->getIdRecursoPadre ());
					$idValidacion = $this->getIdPadreIdPadre($IdPadreIdPadre);
					$listacomites = array (277,278,279,280,303,304,305,306,309,323,225,327,328);
					if(in_array($idValidacion,$listacomites)){
						
						$nombre = $this->getNombreDirectorio($IdPadreIdPadre). " - " . $mes;
					}else{
					$nombre = $this->getNombreDirectorio ( $recurso->getIdRecursoPadre () ) . " - " . $mes;
					}
				} else {
					$nombre = $this->getNombreDirectorio ( $recurso->getIdRecursoPadre () ) . " - " . $recurso->getDescripcionRecurso ();
				}
			}
			$cadena .= "  <h6><a href='javascript:Descargar(" . $recurso->getIdRecurso () . ")'>" . $nombre . "</a></h6>
                                    <p>" . $this->EncontrarSeccion ( $recurso->getIdRecursoPadre () ) . "
                                        <span class='text-muted'><i class='fa fa-clock-o'></i>  " . $this->getTiempoPublicacion ( $recurso->getFechaRecurso () ) . " </span>
                                    </p>";
		}
		$this->response->setContent ( $cadena );
		return $this->response;
	}

	public function PoblarAnunciosAction() {
		$anuncio = $this->entityManager->getRepository ( Anuncios::class )->findBy ( array (), array ('idanuncio' => 'ASC'), 3 );
		if (is_array ( $anuncio )) {
			$anuncios = $anuncio;
		} else
			$anuncios [0] = $anuncio;
		$cadena = "";
		foreach ( $anuncios as $anuncio ) {
			$cadena .= "<div> 
    					<div class='widget-int num-count' style='text-align:center'>" . $anuncio->getCampo1 () . "</div>
                                 <div class='widget-title' style='text-align:center'>" . $anuncio->getCampo2 () . "</div>
                                 <div class='widget-subtitle' style='text-align:center'>" . $anuncio->getCampo3 () . "</div>
                          </div>";
			$this->response->setContent ( $cadena );
			return $this->response;
		}
	}

	public function getNombreDirectorio($idrecurso) {
		$qb = $this->entityManager->createQueryBuilder ();
		$q = $qb->select ( 'u.DescripcionRecurso' )->from ( 'Application\Entity\Recurso', 'u' )->where ( 'u.idRecurso = ?1' )->setParameter ( 1, $idrecurso )->getQuery ();
		$p = $q->execute ();
		$p = $p [0];
		$recurso = implode ( '.', $p );
		return $recurso;
	}

	public function getIdPadreIdPadre($idrecurso){
		$qb = $this->entityManager->createQueryBuilder ();
		$q = $qb->select ( 'u.idRecursoPadre' )->from ( 'Application\Entity\Recurso', 'u' )->where ( 'u.idRecurso = ?1' )->setParameter ( 1, $idrecurso )->getQuery ();
		$p = $q->execute ();
		$p = $p [0];
		$recurso = implode ( '.', $p );
		return $recurso;
	}
	
	public function getTiempoPublicacion($fecha) {
		$timestamp = strtotime ( $fecha );
		$diff = time () - ( int ) $timestamp;
		if ($diff == 0)
			return 'justo ahora';
		$intervals = array (
		 1 => array ('año',31556926),
		 $diff < 31556926 => array ('mes',2628000),
		 $diff < 2629744 => array ('semana',604800),
		 $diff < 604800 => array ('dia',86400),
		 $diff < 86400 => array ('hora',3600),
		 $diff < 3600 => array ('minuto',60),
		 $diff < 60 => array ('segundo',1));
		$value = floor ( $diff / $intervals [1] [1] );
		return 'hace '.$value . ' ' . $intervals [1] [0] . ($value > 1 ? 's' : '') . ' atras.';
	}
	
	// FUNCION DE PRUEBA, BORRAR EN UN FUTURO
	public function EncontrarSeccion($idPadre) {
		$SeccionArray = array ("multiclientes","infoteca","induccion","guiateca","galeria","comites");
		$ruta = $this->crearRuta ( $idPadre );
		$posiblesSecciones = explode ( '/', $ruta );
		$variable = "_";
		if (strpos ( $posiblesSecciones [0], $variable ) == true) {
			$seccion = explode ( "_", $posiblesSecciones [0] );
			$seccion = $seccion [0];
		} else {
			$seccion = array_intersect ( $SeccionArray, $posiblesSecciones );
			$seccion = implode ( "", $seccion );
		}
		return $seccion;
	}

	public function crearRuta($idruta) {
		$idPadre = $idruta;
		$ruta = "";
		while ( $idPadre != Null ) {
			$recursopadre = $this->entityManager->getRepository ( Recurso::class )->findBy ( array ('idRecurso' => $idPadre 
			) );
			if (is_array ( $recursopadre )) {
				$recursospadre = $recursopadre;
			} else
				$recursospadre [0] = $recursopadre;
			foreach ( $recursospadre as $recursopadre ) {
				$padre = $recursopadre->getIdRecursoPadre ();
				$nombre = $recursopadre->getRecurso ();
				$idPadre = $padre;
				$ruta = $nombre . "/" . $ruta;
			}
			if ($idPadre == '0') {
				break;
			}
		}
		return $ruta;
	}

	public function ConvertirAMes($mesnumero) {
		$mes = $mesnumero;
		setlocale(LC_TIME, 'spanish'); 
		if ($mes >= 1 && $mes <= 12) {
			$nombremes = strftime("%B",mktime(0, 0, 0, $mes, 1, 2000));
		}
		return $nombremes;
	}

	public function PoblarActasAction() {
		$rsm = new ResultSetMapping ();
		$rsm->addEntityResult ( 'Application\Entity\Recurso', 'r' );
		$rsm->addFieldResult ( 'r', 'idRecursoPadre', 'idRecursoPadre' );
		$rsm->addFieldResult ( 'r', 'idRecurso', 'idRecurso' );
		$rsm->addFieldResult ( 'r', 'DescripcionRecurso', 'DescripcionRecurso' );
		$rsm->addFieldResult ( 'r', 'Recurso', 'Recurso' );
		$rsm->addFieldResult ( 'r', 'TipoRecurso', 'TipoRecurso' );
		$rsm->addFieldResult ( 'r', 'ExtensionRecurso', 'ExtensionRecurso' );
		$rsm->addFieldResult ( 'r', 'TipoDirectorio', 'TipoDirectorio' );
		$rsm->addFieldResult ( 'r', 'FechaRecurso', 'FechaRecurso' );
		$rsm->addFieldResult ( 'r', 'UrlRecurso', 'UrlRecurso' );
		$em = $this->entityManager;
		$qb = $em->createNativeQuery ( "CALL pa_ActasSEL (:a,:b,:c)", $rsm );
		$qb->setParameters ( array ('a' => 274,'b' => 275,'c' => 276) );
		$qb->execute ();
		$em->flush ();
		$recursos = $qb->getResult ();
		$cadena = "";
		
		foreach ( $recursos as $recurso ) {
			$nombre = $recurso->getDescripcionRecurso ();
			if (is_numeric ( $nombre )) {
				if ($nombre > 0 && $nombre < 13) {
					$mes = $this->ConvertirAMes ( $nombre );
					$IdPadreIdPadre  = $this->getIdPadreIdPadre($recurso->getIdRecursoPadre ());
					$idValidacion = $this->getIdPadreIdPadre($IdPadreIdPadre);
					$listacomites = array (277,278,279,280,303,304,305,306,309,323,225,327,328);
					if(in_array($idValidacion,$listacomites)){
						$nombre = $this->getNombreDirectorio($IdPadreIdPadre). " - " . $mes;
					}else{
					$nombre = $this->getNombreDirectorio ( $recurso->getIdRecursoPadre () ) . " - " . $mes;
					}
				} else {
					$nombre = $this->getNombreDirectorio ( $recurso->getIdRecursoPadre () ) . " - " . $recurso->getDescripcionRecurso ();
				}
			}
			$cadena .= "  <h6><a href='javascript:Descargar(" . $recurso->getIdRecurso () . ")'>" . $nombre . "</a></h6>
                                    <p>" . $this->EncontrarSeccion ( $recurso->getIdRecursoPadre () ) . "
                                        <span class='text-muted'><i class='fa fa-clock-o'></i>  " . $this->getTiempoPublicacion ( $recurso->getFechaRecurso () ) . " </span>
                                    </p>";
		}
		$this->response->setContent ( $cadena );
		return $this->response;
	}

	public function ObtenerIdPadre($idHijo) {
		$qb = $this->entityManager->createQueryBuilder ();
		$q = $qb->select ( 'u.idRecursoPadre' )->from ( 'Application\Entity\Recurso', 'u' )->where ( 'u.idRecurso = ?1' )->setParameter ( 1, $idHijo )->getQuery ();
		$p = $q->execute ();
		$p = $p [0];
		$idpadre = implode ( '.', $p );
		return $idpadre;
	}
}