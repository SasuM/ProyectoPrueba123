<?php

/**
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */
namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Application\Entity\Recurso;
use Application\Entity\Papelera;
use Application\Entity\UsuarioRol;
use Application\Entity\RecursoRol;
use Application\Entity\Auditoria;
use Zend\View\Model\ViewModel;
use Zend\Mail\Message;
use Zend\Mail\Transport\Smtp as SmtpTransport;
use Zend\Mail\Transport\SmtpOptions;
use Zend\Mime\Message as MimeMessage;
use Zend\Mime\Part as MimePart;


class UploadController extends AbstractActionController {
	/**
	 * Entity manager.
	 *
	 * @var Doctrine\ORM\EntityManager
	 */
	private $entityManager;
	public function __construct($entityManager) {
		$this->entityManager = $entityManager;
	}
	
	// Action principal que inicia la gestion de subida de archivos , Recepciona un idPadre y genera una ruta para posteriormente
	// crear directorios y subir los archivos a sus respectivas carpetas, ademas convierte automaticamente los caracteres no validos
	// de un archivo y los sube a la base de datos.
	// Se le agrego una condicional en el caso de que la subida de archivos sea de tipo historico (años) , si es asi tomaria por nombre
	// el año del archivo y la carpeta padre se crearia apartir de un id y se guardaria en la base de datos
	public function UploadAction() {
		$previousUrl = $_SESSION['ContainerUrl']['url'];
		$type = $this->getRequest ()->getPost ( "type" );
		if ($type == "historico") {
			$radio = $this->getRequest ()->getPost ( "iradio" );
			if ($radio == "existente") {
				$archivoid = $this->getRequest ()->getPost ( "archivo" );
				$archivoconpunto = $this->BuscarRecursoporId ( $archivoid );
				$archivo = substr ( $archivoconpunto, 0, - 1 );
				$anio = $this->getRequest ()->getPost ( "anio" );
				$idruta = $this->getRequest ()->getPost ( "idPadre" );
				$uploaddir = 'public/upload/' . $this->CrearRuta ( $idruta );
				if (! file_exists ( $uploaddir . $archivo )) {
					return	$this->redirect()->toUrl($previousUrl);
					echo "Directorio no Existe . Error - Conctacte con su administrador";
				} else {
					$uploadfile = $uploaddir . $archivo;
					$file = $_FILES ['file2'] ['name'];
					$extension = pathinfo ( $file, PATHINFO_EXTENSION );
					if (! file_exists ( $uploaddir )) {
						mkdir ( $uploaddir, 0777, true );
						chmod ( $uploaddir, 0777 );
					}
					if (move_uploaded_file ( $_FILES ['file2'] ['tmp_name'], $uploadfile . "/" . $anio . "." . $extension )) {
						if ($archivoid != '-1') {
							$idreturn = $this->AgregarRecurso ( $archivoid, $anio, $anio, 'A', $extension, null );
							$this->agregarAuditoria($idreturn, 'Subida');
							return	$this->redirect()->toUrl($previousUrl);
						} else {
							return	$this->redirect()->toUrl($previousUrl);
						}
					} else {
						return	$this->redirect()->toUrl($previousUrl);
					}
					return	$this->redirect()->toUrl($previousUrl);
				}
			} else {
				$nombrerecurso = $this->getRequest ()->getPost ( "archivo2" );
				$recurso = $this->ConvertirCadena ( $nombrerecurso, "Normal" );
				$anio = $this->getRequest ()->getPost ( "anio2" );
				$idruta = $this->getRequest ()->getPost ( "idPadre" );
				$uploaddir = 'public/upload/' . $this->CrearRuta ( $idruta );
				if ($nombrerecurso <= 5) {
					echo "<script>
						toastr.error('Nombre incorrecto o ya existente.');
							</script>";
				}
				if (! file_exists ( $uploaddir . $recurso )) {
					mkdir ( $uploaddir . $recurso, 0777, true );
					chmod ( $uploaddir, 0777 );
					$nuevorecurso = $this->AgregarRecursoReturn ( $idruta, $nombrerecurso, $recurso, 'D', null, 'H' );
				}
				$uploadfile = $uploaddir . $recurso;
				$file = $_FILES ['file2'] ['name']; //
				$extension = pathinfo ( $file, PATHINFO_EXTENSION );
				echo '<pre>';
				if (move_uploaded_file ( $_FILES ['file2'] ['tmp_name'], $uploadfile . "/" . $anio . "." . $extension )) {
					$idreturn = $this->AgregarRecurso ( $nuevorecurso, $anio, $anio, 'A', $extension, null );
					$this->agregarAuditoria($idreturn, 'Subida');
					return	$this->redirect()->toUrl($previousUrl);
				} else {
					return	$this->redirect()->toUrl($previousUrl);
				}
				return	$this->redirect()->toUrl($previousUrl);
			}
		}
		if ($type == null) {
			$idruta = $this->getRequest ()->getPost ( "idPadre" );
			$uploaddir = 'public/upload/' . $this->CrearRuta ( $idruta );
			$nombreconvertido = $this->ConvertirCadena ( $_FILES ['file'] ['name'], 'Archivo' );
			$uploadfile = $uploaddir . $nombreconvertido;
			$file = $_FILES ['file'] ['name'];
			$filename = $_FILES ['filename'];
			$nombre = pathinfo ( $file, PATHINFO_FILENAME );
			$extension = pathinfo ( $file, PATHINFO_EXTENSION );
			$recurso = $nombre;
			$recurso = $this->ConvertirCadena ( $recurso, 'Subida' );
			if (! file_exists ( $uploaddir )) {
				mkdir ( $uploaddir, 0777, true );
				chmod ( $uploaddir, 0777 );
			}
			echo '<pre>';
			if (move_uploaded_file ( $_FILES ['file'] ['tmp_name'], $uploadfile )) {
				echo "File is valid, and was successfully uploaded.\n";
				echo $ruta;
				$idreturn = $this->AgregarRecurso ( $idruta, $nombre, $recurso, 'A', strtolower ( $extension ), null );
				$this->agregarAuditoria($idreturn, 'Subida');
				return $this->response;
				echo "Archivo Registrado en Base de Datos";
			} else {
				echo "Possible file upload attack!\n";
				echo $ruta;
			}
			return	$this->redirect()->toUrl($previousUrl);
		}
		if ($type == "historicomes") {
			$radio = $this->getRequest ()->getPost ( "iradio" );
			if ($radio == "existente") {
				$archivoid = $this->getRequest ()->getPost ( "archivo" );
				$archivoconpunto = $this->BuscarRecursoporId ( $archivoid );
				$archivo = substr ( $archivoconpunto, 0, - 1 );
				$anio = $this->getRequest ()->getPost ( "anio" );
				$idruta = $this->getRequest ()->getPost ( "idPadre" );
				$uploaddir = 'public/upload/' . $this->CrearRuta ( $idruta );
				if (! file_exists ( $uploaddir . $archivo )) {
					echo "Directorio no Existe . Error - Conctacte con su administrador";
				} else {
					$uploadfile = $uploaddir . $archivo;
					$file = $_FILES ['file2'] ['name'];
					$extension = pathinfo ( $file, PATHINFO_EXTENSION );
					if (! file_exists ( $uploaddir )) {
						mkdir ( $uploaddir, 0777, true );
						chmod ( $uploaddir, 0777 );
					}
					echo '<pre>';
					if (move_uploaded_file ( $_FILES ['file2'] ['tmp_name'], $uploadfile . "/" . $anio . "." . $extension )) {
						echo "File is valid, and was successfully uploaded.\n";
						echo $ruta;
						$idreturn = $this->AgregarRecurso ( $archivoid, $anio, $anio, 'A', $extension, null );
						$this->agregarAuditoria($idreturn, 'Subida');
						return	$this->redirect()->toUrl($previousUrl);
					} else {
						return	$this->redirect()->toUrl($previousUrl);
					}
					 return	$this->redirect()->toUrl($previousUrl);
				}
			} else {
				$nombrerecurso = $this->getRequest ()->getPost ( "archivo2" );
				$recurso = $this->ConvertirCadena ( $nombrerecurso, "Normal" );
				$anio = $this->getRequest ()->getPost ( "anio2" );
				$idruta = $this->getRequest ()->getPost ( "idPadre" );
				$uploaddir = 'public/upload/' . $this->CrearRuta ( $idruta );
				if (! file_exists ( $uploaddir . $recurso )) {
					mkdir ( $uploaddir . $recurso, 0777, true );
					chmod ( $uploaddir, 0777 );
					$nuevorecurso = $this->AgregarRecursoReturn ( $idruta, $nombrerecurso, $recurso, 'D', null, 'H' );
				}
				$uploadfile = $uploaddir . $recurso;
				$file = $_FILES ['file2'] ['name']; //
				$extension = pathinfo ( $file, PATHINFO_EXTENSION );
				echo '<pre>';
				if (move_uploaded_file ( $_FILES ['file2'] ['tmp_name'], $uploadfile . "/" . $anio . "." . $extension )) {
					
					echo "File is valid, and was successfully uploaded.\n";
					echo $ruta;
					$idreturn = $this->AgregarRecurso ( $nuevorecurso, $anio, $anio, 'A', $extension, null );
					$this->agregarAuditoria($idreturn, 'Subida');
					return	$this->redirect()->toUrl($previousUrl);
				} else {
					return	$this->redirect()->toUrl($previousUrl);
				}
				return	$this->redirect()->toUrl($previousUrl);
			}
		}
	}
	
	// Metodo que recibe el idPadre para hacer la busqueda de todos los registros con ese id e ir buscando los recursos padres
	// hasta que sea null , luego los va concatenando y genera la ruta del recurso.
	public function CrearRuta($idruta) {
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
	
	// Permite agregar un recurso
	public function AgregarRecurso($idpadre, $descripcion, $nombre, $tiporecurso, $extensionrecurso, $tipodir, $urlrecurso) {
		date_default_timezone_set("America/Lima");
		$fecha = date ( "Y-m-d H:i:s" );
		$recurso = new Recurso ();
		$recurso->setIdRecursoPadre ( $idpadre );
		$recurso->setDescripcionRecurso ( $descripcion );
		$recurso->setRecurso ( $nombre );
		$recurso->setTipoRecurso ( $tiporecurso );
		$recurso->setExtensionRecurso ( $extensionrecurso );
		$recurso->setTipoDirectorio ( $tipodir );
		$recurso->setFechaRecurso ( $fecha );
		$recurso->setUrlRecurso ( $urlrecurso );
		$recurso->setFlag(1);
		$this->entityManager->persist ( $recurso );
		$this->entityManager->flush ();
		$id = $recurso->getIdRecurso();
		return $id;
	}
	public function AgregarRecursoReturn($idpadre, $descripcion, $nombre, $tiporecurso, $extensionrecurso, $tipodir) {
		date_default_timezone_set("America/Lima");
		$fecha = date ( "Y-m-d H:i:s" );
		$recurso = new Recurso ();
		$recurso->setIdRecursoPadre ( $idpadre );
		$recurso->setDescripcionRecurso ( $descripcion );
		$recurso->setRecurso ( $nombre );
		$recurso->setTipoRecurso ( $tiporecurso );
		$recurso->setExtensionRecurso ( $extensionrecurso );
		$recurso->setTipoDirectorio ( $tipodir );
		$recurso->setFechaRecurso ( $fecha );
		$recurso->setFlag(1);
		$this->entityManager->persist ( $recurso );
		$this->entityManager->flush ();
		$id = $recurso->getIdRecurso ();
		return $id;
	}
	
	// Este action permite editar el nombre de un recurso tanto en la base de datos como en el directorio ("rename")
	public function EditarRecursoAction() {
		
		$idrecurso = $this->getRequest ()->getPost ( "idrecurso" );
		$nuevonombre = $this->getRequest ()->getPost ( "nombrefile" );
		$nuevonombrec = $this->ConvertirCadena ( $nuevonombre, 'Normal' );
		$recurso = $this->entityManager->getRepository ( Recurso::class )->findBy ( array ('idRecurso' => $idrecurso) );
		$tipoDirectorio = $recurso [0]->getTipoDirectorio ();
		$tipoRecurso = $recurso [0]->getTipoRecurso ();
		if ($tipoRecurso == 'D') {
			if ($tipoDirectorio == 'H') {
				$idP2 = $this->ObtenerIdPadre ( $idrecurso );
				$idPadre = $this->ObtenerIdPadre ( $idP2 );
			} else {
				$idPadre = $this->ObtenerIdPadre ( $idrecurso );
				$idP2 = $idPadre;
			}
		} else {
			$idP2 = $this->ObtenerIdPadre ( $idrecurso );
			$idPadre = $this->ObtenerIdPadre ( $idP2 );
		}
		$valida = $this->ValidarPermisosEditar ( $idPadre );
		$validanombre = $this->ValidarNombreExistente ( $nuevonombrec, $idP2 ); // Si retorna true es porque ya existe
		if ($validanombre == false) {
			if ($valida == true) {
				$extension = $this->BuscarRecursoporId ( $idrecurso );
				$extension = end ( explode ( '.', $extension ) );
				$idpadre = $this->ObtenerIdPadre ( $idrecurso );
				$directorio = $this->crearRuta ( $idrecurso );
				$directorio = substr ( $directorio, 0, - 1 );
				$path = 'public/upload/' . $directorio . "." . $extension;
				$path2 = 'public/upload/' . $this->crearRuta ( $idpadre ) . $nuevonombrec . "." . $extension;

				
				if ($tipoRecurso == 'D') {
					$path = substr ( $path, 0, strlen ( $path ) - 1 );
					$path2 = substr ( $path2, 0, strlen ( $path2 ) - 1 );
					chmod ( $path, 0777 );
				}
				if (strlen ( $nuevonombre ) < 5) {
					return $this->redirect ()->toUrl ( $ruta );
				} else {
					
					if (! file_exists ( $path2 )) {
						rename ( $path, $path2 );
						$ruta = $this->getRequest ()->getPost ( 'url2', null );
						$qb = $this->entityManager->createQueryBuilder ();
						$q = $qb->update ( 'Application\Entity\Recurso', 'u' )->set ( 'u.DescripcionRecurso', '?1' )->set ( 'u.Recurso', '?2' )->where ( 'u.idRecurso = ?3' )->setParameter ( 1, $nuevonombre )->setParameter ( 2, $nuevonombrec )->setParameter ( 3, $idrecurso )->getQuery ();
						$p = $q->execute ();
						$this->agregarAuditoria($idrecurso, 'Actualización');
						return $this->redirect ()->toUrl ( $ruta );
						return $this->response;
					} else {
						$this->getResponse ()->setStatusCode ( 404 ); // SI NO EXISTE LA RUTA
						return;
					}
				}
			} else {
				$this->getResponse ()->setStatusCode ( 404 ); // SI NO TIENE PERMISOS DE EDICION
				return;
			}
		} else {
			$this->getResponse ()->setStatusCode ( 404 ); // Si EL NOMBRE YA EXISTE
			return;
		}
	}

	public function EditarRecursoMulticlienteAction() {
		
		$idrecurso = $this->getRequest ()->getPost ( "idrecursoinforme" );
		$nuevonombre = $this->getRequest ()->getPost ( "mnombrefile" );
		$codestudio = $this->getRequest()->getPost("codestudio");
		$nuevonombrec = $this->ConvertirCadena ( $nuevonombre, 'Normal' );

	
		$recurso = $this->entityManager->getRepository ( Recurso::class )->findBy ( array ('idRecurso' => $idrecurso) );
		$nombrebd = $recurso[0]->getDescripcionRecurso();

		
		$tipoDirectorio = $recurso [0]->getTipoDirectorio ();
		$tipoRecurso = $recurso [0]->getTipoRecurso ();
		if ($tipoRecurso == 'D') {
			if ($tipoDirectorio == 'H') {
				$idP2 = $this->ObtenerIdPadre ( $idrecurso );
				$idPadre = $this->ObtenerIdPadre ( $idP2 );
			} else {
				$idPadre = $this->ObtenerIdPadre ( $idrecurso );
				$idP2 = $idPadre;
			}
		} else {
			$idP2 = $this->ObtenerIdPadre ( $idrecurso );
			$idPadre = $this->ObtenerIdPadre ( $idP2 );
		}
		$valida = $this->ValidarPermisosEditar ( $idPadre );
		$validanombre = $this->ValidarNombreExistente ( $nuevonombrec, $idP2 ); // Si retorna true es porque ya existe
		if($nombrebd == $nuevonombre){ $validanombre = false;}

		
		if ($validanombre == false) {

			
			if ($valida == true) {
			
				$extension = $this->BuscarRecursoporId ( $idrecurso );
				$extension = end ( explode ( '.', $extension ) );
				$idpadre = $this->ObtenerIdPadre ( $idrecurso );
				$directorio = $this->crearRuta ( $idrecurso );
				$directorio = substr ( $directorio, 0, - 1 );
				$path = 'public/upload/' . $directorio . "." . $extension;
				$path2 = 'public/upload/' . $this->crearRuta ( $idpadre ) . $nuevonombrec . "." . $extension;

				
				if ($tipoRecurso == 'D') {
					$path = substr ( $path, 0, strlen ( $path ) - 1 );
					$path2 = substr ( $path2, 0, strlen ( $path2 ) - 1 );
					chmod ( $path, 0777 );
				}
				if (strlen ( $nuevonombre ) < 5) {
					return $this->redirect ()->toUrl ( $ruta );
				} else {
				
					
						rename ( $path, $path2 );
						$ruta = $this->getRequest ()->getPost ( 'murl2', null );
						$qb = $this->entityManager->createQueryBuilder ();
						$q = $qb->update ( 'Application\Entity\Recurso', 'u' )
						->set ( 'u.DescripcionRecurso', '?1' )
						->set ( 'u.Recurso', '?2' )
						->set ( 'u.CodEstudio', '?3')
						->where ( 'u.idRecurso = ?4' )
						->setParameter ( 1, $nuevonombre )
						->setParameter ( 2, $nuevonombrec )
						->setParameter ( 3, $codestudio )
						->setParameter ( 4, $idrecurso )
						->getQuery ();
						$p = $q->execute ();
						$this->agregarAuditoria($idrecurso, 'Actualización');
						return $this->redirect ()->toUrl ( $ruta );
						return $this->response;
					
				}
			} else {
				$this->getResponse ()->setStatusCode ( 404 ); // SI NO TIENE PERMISOS DE EDICION
				return;
			}
		} else {
			$this->getResponse ()->setStatusCode ( 404 ); // Si EL NOMBRE YA EXISTE
			return;
		}
	}
	
	// Este action Permite crear un directorio a partir de una ruta(url)
	public function CrearSeccionAction() {
		$ruta = $this->getRequest ()->getPost ( 'url', null );
		$nombre = $this->getRequest ()->getPost ( 'nombredir', null );
		$directorio = $this->ConvertirCadena ( $nombre, 'Normal' );
		$codigo = $this->getRequest ()->getPost ( 'parent', null ); // idPadre
		$validarnombre = $this->ValidarNombreExistente ( $directorio, $codigo );
		if ($nombre == "") {
			$this->getResponse ()->setStatusCode ( 500 ); // SI NO TIENE PERMISOS PARA DESCARGAR LE MANDO LA PAGINA DE ERROR
			return;
		}
		if ($validarnombre == false) { // Si es true es porque existe un nombre identico
			$uploaddir = 'public/upload/' . $this->CrearRuta ( $codigo ) . $directorio;
			if (strlen ( $nombre ) < 5) {
				return $this->redirect ()->toUrl ( $ruta );
			} else {
				mkdir ( $uploaddir, 0777, true );
				chmod ( $uploaddir, 0777 );
				
				$idreturn = $this->AgregarRecurso ( $codigo, $nombre, $directorio, 'D', null, 'N' );
				$this->agregarAuditoria($idreturn, 'Sección');
				return $this->redirect ()->toUrl ( $ruta );
			}
		} else {
			$this->getResponse ()->setStatusCode ( 404 ); // SI NO TIENE PERMISOS PARA DESCARGAR LE MANDO LA PAGINA DE ERROR
			return;
		}
	}
	
	// Funcion que recibe una cadena y la convierte a caracteres válidos y en minuscula
	public function ConvertirCadena($string, $tipo) {
		$string = trim ( $string );
		$string = strtolower ( $string );
		$string = str_replace ( array ('á','à','ä','â','ª','Á','À','Â','Ä'), array ('a','a','a','a','a','A','A','A','A'), $string );
		$string = str_replace ( array ('é','è','ë','ê','É','È','Ê','Ë'), array ('e','e','e','e','E','E','E','E'), $string );
		$string = str_replace ( array ('í','ì','ï','î','Í','Ì','Ï','Î'), array ('i','i','i','i','I','I','I','I'), $string );
		$string = str_replace ( array ('ó','ò','ö','ô','Ó','Ò','Ö','Ô'), array ('o','o','o','o','O','O','O','O' ), $string );
		$string = str_replace ( array ('ú','ù','ü','û','Ú','Ù','Û','Ü'), array ('u','u','u','u','U','U','U','U'), $string );
		$string = str_replace ( array ('ñ','Ñ','ç','Ç'), array ('n','N','c','C'), $string );
		if ($tipo == 'Normal') {
			$string = str_replace ( array ("\\","¨","º","-","~","#","@","|","!","\"","·","$","%","&","/","?","'","¡","¿","[","^","`","]","+","}","{","¨","´",">","< ",";",",",":","." ), '', $string );
		} else {
			$string = str_replace ( array ("\\","¨","º","-","~","#","@","|","!","\"","$","%","&","/","?","'","¡","¿","[","^","`","]","+","}","{","¨","´",">","< ",";",",",":" ), '', $string );
		}
		$string = str_replace ( array ('(',')',' '), array ('-','-','_' ), $string );
		return strtolower ( $string );
	}
	
	// Este action permite ejecutar la Descarga de un recurso , recepcionando el "id" de este y luego hace toda la logica de descarga
	public function DescargarRecursoAction() {
		$idRecurso = $this->getEvent ()->getRouteMatch ()->getParam ( 'id' );
		$AjaxValidation = $this->getRequest ()->getPost ( 'validar', null );
		$idp = $this->ObtenerIdPadre ( $idRecurso );
		$recurso = $this->entityManager->getRepository ( Recurso::class )->findBy ( array ('idRecurso' => $idp) );
		$tipoDirectorio = $recurso [0]->getTipoDirectorio ();
		if ($tipoDirectorio == 'H') {
			$idP2 = $this->ObtenerIdPadre ( $idp );
			$idPadre = $this->ObtenerIdPadre ( $idP2 );
			$valida = $this->ValidarPermisosDescargar ( $idPadre );
		} else {
			$idPadre = $this->ObtenerIdPadre ( $idp );
			$valida = $this->ValidarPermisosDescargar ( $idPadre );
		}
		if ($valida == true) {
			
			$ruta = $this->crearRuta ( $idRecurso );
			$ruta1 = explode ( "/", substr ( $ruta, 0, - 1 ) );
			$ruta2 = array_pop ( $ruta1 );
			$rutafinal = implode ( "/", $ruta1 );
			$fullPath = 'public/upload/' . $rutafinal;
			$filename = $this->BuscarRecursoporId ( $idRecurso );
			$file = $fullPath . "/" . $filename;

			if($idPadre == 107){
				$ruta = $this->crearRuta($idRecurso);
				$ruta1 = explode ( "/", substr ( $ruta, 0, - 1 ) );
				$ruta2 = array_pop ( $ruta1 );
				$recursoinforme = $this->entityManager->getRepository ( Recurso::class )->findBy ( array ('idRecurso' => $idRecurso ));
				$tipoinforme = $recursoinforme[0]->getTipoInforme();
				$rutafinal = implode ( "/", $ruta1 );
				$fullPath = 'public/upload/' . $rutafinal;
				$filename = $this->BuscarRecursoporId ( $idRecurso );
				$file = $fullPath ."/" . $tipoinforme ."/". $filename;
				
			}
			if (! file_exists ( $file )) { // file does not exist
				$this->getResponse ()->setStatusCode ( 404 );
				return;
			} else {
				if($AjaxValidation <> 1){
				$this->agregarAuditoria($idRecurso,'Descarga');
				$fp = fopen ( $file, 'rb' );
				header ( "Cache-Control: public" );
				header ( "Content-Description: File Transfer" );
				header ( 'Content-Type: application/octet-stream' );
				header ( "Content-Disposition: attachment; filename=$filename" );
				header ( 'Expires: 0' );
				header ( 'Cache-Control: must-revalidate' );
				header ( 'Pragma: public' );
				header ( "Content-Length: " . filesize ( $file ) );
				fpassthru ( $fp );
			}
				return $this->response;
			}
		} else
			$this->getResponse ()->setStatusCode ( 404 ); // SI NO TIENE PERMISOS PARA DESCARGAR LE MANDO LA PAGINA DE ERROR
		return;
	}

		// Esta funcion retorna el nombre del recurso a partir de una idRecurso
	public function BuscarRecursoporId($idrecurso) {
		$qb = $this->entityManager->createQueryBuilder ();
		$q = $qb->select ( 'u.Recurso,u.ExtensionRecurso' )->from ( 'Application\Entity\Recurso', 'u' )->where ( 'u.idRecurso = ?1' )->setParameter ( 1, $idrecurso )->getQuery ();
		$p = $q->execute ();
		$p = $p [0];
		$recurso = implode ( '.', $p );
		return $recurso;
	}
		// Esta funcion Permite obtener el idPadreRecurso mandandole el idRecurso
	public function ObtenerIdPadre($idHijo) {
		$qb = $this->entityManager->createQueryBuilder ();
		$q = $qb->select ( 'u.idRecursoPadre' )->from ( 'Application\Entity\Recurso', 'u' )->where ( 'u.idRecurso = ?1' )->setParameter ( 1, $idHijo )->getQuery ();
		$p = $q->execute ();
		$p = $p [0];
		$idpadre = implode ( '.', $p );
		return $idpadre;
	}
	
	// Este Action permite eliminar un recurso sea carpeta o archivo, se le envia desde el url el idrecurso anteponiendo una letra
	// A para archivo y D para directorio , Con esa letra se identifica que es lo que se va a a realizar
	// Si es Tipo directorio se tendra que borrar primero todos los archivos dentro de la papeta y luego borrarla
	// OJO : PRIMERO SE TIENE QUE ELIMINAR TODAS LAS CARPETAS O DIRECTORIOS DESPUES SE ELIMINA EL DIRECTORIO PRINCIPAL
	public function EliminarRecursoAction() {
		$route = $this->getRequest ()->getPost ( 'route', null );
		$idRecurso = $this->getEvent ()->getRouteMatch ()->getParam ( 'id' );
		if ($idRecurso [0] == 'A') {
			$idRecursoUrl = ltrim ( $idRecurso, 'A' );
		} 
		else {
			$idRecursoUrl = ltrim ( $idRecurso, 'D' );
		}
		$verificahijos = $this->verificarHijos ( $idRecursoUrl );
		$recurso = $this->entityManager->getRepository ( Recurso::class )->findBy ( array ('idRecurso' => $idRecursoUrl) );
		$tipoDirectorio = $recurso [0]->getTipoDirectorio ();
		$tipoRecurso = $recurso [0]->getTipoRecurso ();
		if ($tipoRecurso == 'D') {
			if ($tipoDirectorio == 'H') {
				if ($verificahijos [0] != null) {
					$idP2 = $this->ObtenerIdPadre ( $idRecursoUrl );
					$idP3 = $this->ObtenerIdPadre ( $idP2 );
					$idPadre = $this->ObtenerIdPadre ( $idP3 );
				} else {
					$idP2 = $this->ObtenerIdPadre ( $idRecursoUrl );
					$idPadre = $this->ObtenerIdPadre ( $idP2 );
				}
			} 
			else {
				$idPadre = $this->ObtenerIdPadre ( $idRecursoUrl );
			}
		} else {
			$idP2 = $this->ObtenerIdPadre ( $idRecursoUrl );
			$recurso2 = $this->entityManager->getRepository ( Recurso::class )->findBy ( array ('idRecurso' => $idP2) );
			$tipoDirectorio2 = $recurso2 [0]->getTipoDirectorio ();
			if ($tipoDirectorio2 == 'H') {
				$idP3 = $this->ObtenerIdPadre ( $idP2 );
				$idPadre = $this->ObtenerIdPadre ( $idP3 );
			} else {
				$idPadre = $this->ObtenerIdPadre ( $idP2 );
			}
		}
		$valida = $this->ValidarPermisosEditar ( $idPadre );
		if ($valida == true) {
			if ($idRecurso [0] == 'A') {
				$idRecurso = ltrim ( $idRecurso, 'A' );
				$idPadre = $this->ObtenerIdPadre ( $idRecurso );
				$nombreRecurso = $this->BuscarRecursoporId ( $idRecurso );
				$ruta = 'public/upload/' . $this->CrearRuta ( $idPadre ) . $nombreRecurso;
				date_default_timezone_set("America/Lima");
				$fecha = date("Y-m-d H:i:s");
				$timestamp = strtotime($fecha);
				$rutapapelera = 'public/upload/papelera/'.$timestamp.'_'.$nombreRecurso;
				if(!copy($ruta,$rutapapelera)){
					echo "Error al pasar a la papelera";
				}
				if (! unlink ( $ruta )) {
					return;
				} else {
					echo ("Archivo borrado : $nombreRecurso");
					$this->AgregarRecursoPapelera($nombreRecurso,$ruta,$rutapapelera);
					$this->EliminarRecurso ( $idRecurso );
					$this->agregarAuditoria($idRecurso, 'Eliminación');
					return $this->response;
				}
			} else {
				$idRecurso = ltrim ( $idRecurso, 'D' );
				$idPadre = $this->ObtenerIdPadre ( $idRecurso );
				$nombreRecurso = $this->BuscarRecursoporId ( $idRecurso );
				$nombreRecurso = rtrim ( $nombreRecurso, "." );
				$ruta = 'public/upload/' . $this->CrearRuta ( $idPadre ) . $nombreRecurso;
				$this->DeleteAllFiles ( $ruta ); // Borro archivos dentro de la carpeta
				$this->EliminarRecursosDirectorio ( $idRecurso ); // Elimino datos en la base de datos de los archivos
				$this->EliminarRecurso ( $idRecurso ); // por ultimo elimino la carpeta en la base de datos
				$this->agregarAuditoria($idRecurso, 'Eliminación');
			}
			return $this->response;
		} else {
			$this->getResponse ()->setStatusCode ( 404 ); // SI NO TIENE PERMISOS DE ELIMINAR
			return $this->response;
		}
	}

	public function EliminarRecursoMulticlienteAction() {
		$route = $this->getRequest ()->getPost ( 'route', null );
		$idRecurso = $this->getEvent ()->getRouteMatch ()->getParam ( 'id' );
		$idRecursoPadre = $this->getREquest()->getPost('idPadre');
			
		
		$valida = $this->ValidarPermisosEditar (107);
		if ($valida == true) {
			
			if($idRecurso == -1 ){
				$idRecurso = $idRecursoPadre;
				$recurso = $this->entityManager->getRepository ( Recurso::class )->findBy ( array ('idRecurso' => $idRecurso) );
				$nombreInforme = $recurso[0]->getRecurso();
				$extensionNombre = $recurso[0]->getExtensionRecurso();
					$idPadre = $this->ObtenerIdPadre ( $idRecurso );
					$nombreRecurso = $this->BuscarRecursoporId ( $idRecurso );
					$nombreRecurso = rtrim ( $nombreRecurso, "." );
					$ruta = 'public/upload/' . $this->CrearRuta ( $idPadre ) . $nombreRecurso;
					
					
			
				
					$this->DeleteAllFiles ( $ruta );
						
						
						
				
						
						echo ("Archivo borrado : $nombreRecurso");
						
						$this->EliminarRecurso ( $idRecurso );
						$this->agregarAuditoria($idRecurso, 'Eliminación');
						return $this->response;
					
			
			}

else{
			$recurso = $this->entityManager->getRepository ( Recurso::class )->findBy ( array ('idRecurso' => $idRecurso) );
			$tipoinforme = $recurso[0]->getTipoInforme();
				$idPadre = $this->ObtenerIdPadre ( $idRecurso );
				$nombreRecurso = $this->BuscarRecursoporId ( $idRecurso );
				
				
				$ruta = 'public/upload/' . $this->CrearRuta ( $idPadre ) . $tipoinforme ."/". $nombreRecurso;

				date_default_timezone_set("America/Lima");
				$fecha = date("Y-m-d H:i:s");
				$timestamp = strtotime($fecha);
				$rutapapelera = 'public/upload/papelera/'.$timestamp.'_'.$nombreRecurso;

				
				if(!copy($ruta,$rutapapelera)){
					echo "Error al pasar a la papelera";
				}
				if (! unlink ( $ruta )) {
					return;
				} else {
					echo ("Archivo borrado : $nombreRecurso");
					$this->AgregarRecursoPapelera($nombreRecurso,$ruta,$rutapapelera);
					$this->EliminarRecurso ( $idRecurso );
					$this->agregarAuditoria($idRecurso, 'Eliminación');
					return $this->response;
				}
			}
			
		} else {
			$this->getResponse ()->setStatusCode ( 404 ); // SI NO TIENE PERMISOS DE ELIMINAR
			return $this->response;
		}
	}
	// Funcion para eliminar un archivo en la base de datos
	public function EliminarRecurso($idrecurso) {
		$qb = $this->entityManager->createQueryBuilder ();
		$qb->delete ( 'Application\Entity\Recurso', 'u' )->where ( 'u.idRecurso = ?1' )->setParameter ( 1, $idrecurso )->getQuery ()->execute ();
	}

	public function AgregarRecursoPapelera($nombre,$rutaAntigua,$rutaNueva){
		date_default_timezone_set("America/Lima");
		$fecha = date ( "Y-m-d H:i:s" );
		$papelera = new Papelera ();
		$papelera->setNombreArchivo ( $nombre );
		$papelera->setRutaAntigua ( $rutaAntigua );
		$papelera->setRutaNueva ( $rutaNueva );
		$papelera->setfechaEliminado ( $fecha );
		$this->entityManager->persist ( $papelera );
		$this->entityManager->flush ();
	}
		// Funcion Para eliminar la Carpeta en la base de datos
	public function EliminarRecursosDirectorio($idrecursopadre) {
		$qb = $this->entityManager->createQueryBuilder ();
		$qb->delete ( 'Application\Entity\Recurso', 'u' )->where ( 'u.idRecursoPadre = ?1' )->setParameter ( 1, $idrecursopadre )->getQuery ()->execute ();
	}
	// Funcion que elimina todos los archivos de una carpeta en un bucle y luego elimina a la carpeta
	public function DeleteAllFiles($dirname) {
		if (is_dir ( $dirname ))
			$dir_handle = opendir ( $dirname );
		if (! $dir_handle)
			return false;
		while ( $file = readdir ( $dir_handle ) ) {
			if ($file != "." && $file != "..") {
				if (! is_dir ( $dirname . "/" . $file )){
				date_default_timezone_set("America/Lima");
				$fecha = date("Y-m-d H:i:s");
				$timestamp = strtotime($fecha);
				$rutapapelera = 'public/upload/papelera/'.$timestamp.'_'.$file;
				if(!copy($dirname."/".$file,$rutapapelera)){
					echo "Error al pasar a la papelera :" . $file;
				}
				$this->AgregarRecursoPapelera($file,$dirname."/".$file,$rutapapelera);
					unlink ( $dirname . "/" . $file );
			}else{
					$this->DeleteAllFiles ( $dirname . '/' . $file );}
			}
		}
		closedir ( $dir_handle );
		rmdir ( $dirname );
	}

	public function agregarAuditoria($idRecurso,$Accion){
		$usuarioAD = $this->identity ();
		date_default_timezone_set("America/Lima");
		$fecha = date ( "Y-m-d H:i:s" );
		$Auditoria = new Auditoria ();
		$Auditoria->setidRecurso ( $idRecurso );
		$Auditoria->setidUsuario ( $usuarioAD );
		$Auditoria->setAccion ( $Accion );
		$Auditoria->setFecha ( $fecha );
		$this->entityManager->persist ( $Auditoria );
		$this->entityManager->flush ();
	}

	public function ValidarPermisosDescargar($idtab) {
		$usuarioAD = $this->identity ();
		$rolusuario = $this->entityManager->getRepository ( UsuarioRol::class )->findOneByidUsuario ( $usuarioAD );
		$rol = $rolusuario->getidRol ();
		$verificarol = $this->entityManager->getRepository ( RecursoRol::class )->findBy ( array ('idRol' => $rol,'idRecurso' => $idtab ) );
		$descargar = $verificarol [0]->getDescargar ();
		return $descargar;
	}

	public function ValidarPermisosEditar($idtab) {
		$usuarioAD = $this->identity ();
		$rolusuario = $this->entityManager->getRepository ( UsuarioRol::class )->findOneByidUsuario ( $usuarioAD );
		$rol = $rolusuario->getidRol ();
		$verificarol = $this->entityManager->getRepository ( RecursoRol::class )->findBy ( array ('idRol' => $rol,'idRecurso' => $idtab) );
		$editar = $verificarol [0]->getEditar ();
		return $editar;
	}

	public function ValidarPermisosEliminar($idtab) {
		$usuarioAD = $this->identity ();
		$rolusuario = $this->entityManager->getRepository ( UsuarioRol::class )->findOneByidUsuario ( $usuarioAD );
		$rol = $rolusuario->getidRol ();
		$verificarol = $this->entityManager->getRepository ( RecursoRol::class )->findBy ( array ('idRol' => $rol,'idRecurso' => $idtab) );
		$eliminar = $verificarol [0]->getEliminar ();
		return $eliminar;
	}

	public function ValidarNombreExistente($nombre, $idpadre) {
		$ruta = 'public/upload/' . $this->CrearRuta ( $idpadre );
		$directorioscan = scandir ( $ruta );
		if (in_array ( $nombre, $directorioscan )) {
			$validanombre = true;
		} else
			$validanombre = false;
		return $validanombre;
	}

	public function getNombreRecursobyIdPadre() {
		$idPadre = $this->getRequest ()->getPost ( "idPadre" );
		$recurso = $this->entityManager->getRepository ( Recurso::class )->findBy ( array ('idRecurso' => $idPadre) );
		$nombrerecurso = $recurso [0]->getDescripcionRecurso ();
		return $nombrerecurso;
	}

	public function setearnombrerecursoAction() {
		$nombrerecurso = $this->getNombreRecursobyIdPadre ();
		$cadena = "";
		$cadena .= $nombrerecurso;
		$this->response->setContent ( $cadena );
		return $this->response;
	}


	public function setearnombresmulticlientesAction(){
		$idPadre = $this->getRequest ()->getPost ( "idPadre" );
		$nombrerecurso = $this->getNombreRecursobyIdPadre ($idPadre);
		$recurso = $this->entityManager->getRepository ( Recurso::class )->findBy ( array ('idRecurso' => $idPadre) );
		$codestudio = $recurso[0]->getCodEstudio();

		$array = array('NombreRecurso' => $nombrerecurso ,'CodEstudio' => $codestudio);
		echo json_encode($array);
		exit;
	}

	public function verificarHijos($recursoPadre) {
		$hijos = $this->entityManager->getRepository ( Recurso::class )->findBy ( array ('idRecursoPadre' => $recursoPadre) );
		return $hijos;
	}

	public function subirImagenAction() {
		$idruta = 1588;
		$uploaddir = 'public/upload/' . $this->CrearRuta ( $idruta );
		$urlLink = $this->getRequest ()->getPost ( "urlimagen" );
		$nombreconvertido = $this->ConvertirCadena ( $_FILES ['imageninicio'] ['name'], 'Archivo' );
		$uploadfile = $uploaddir . $nombreconvertido;
		$file = $_FILES ['imageninicio'] ['name'];
		$nombre = pathinfo ( $file, PATHINFO_FILENAME );
		$extension = pathinfo ( $file, PATHINFO_EXTENSION );
		$recurso = $nombre;
		$recurso = $this->ConvertirCadena ( $recurso, 'Subida' );
		if (! file_exists ( $uploaddir )) {
			mkdir ( $uploaddir, 0777, true );
		}
		echo '<pre>';
		if (move_uploaded_file ( $this->resize_image ( $_FILES ['imageninicio'] ['tmp_name'], 565, 290 ), $uploadfile )) {
			$idreturn = $this->AgregarRecurso ( $idruta, $nombre, $recurso, 'A', strtolower ( $extension ), null, $urlLink );
			$this->agregarAuditoria($idreturn, 'Subida');
			echo "File is valid, and was successfully uploaded.\n";
			$this->redirect ()->toRoute ( 'dashboard' );
		} else {
			$this->redirect ()->toRoute ( 'dashboard' );
		}
	}
	
	// FUNCION PARA REDIMENSIONAR EL TAMAÑO DE LA IMAGEN
	public function resize_image($file, $width, $height) {
		list ( $w, $h ) = getimagesize ( $file );
		/* calculate new image size with ratio */
		$ratio = max ( $width / $w, $height / $h );
		$h = ceil ( $height / $ratio );
		$x = ($w - $width / $ratio) / 2;
		$w = ceil ( $width / $ratio );
		/* read binary data from image file */
		$imgString = file_get_contents ( $file );
		/* create image from string */
		$image = imagecreatefromstring ( $imgString );
		$tmp = imagecreatetruecolor ( $width, $height );
		imagecopyresampled ( $tmp, $image, 0, 0, $x, 0, $width, $height, $w, $h );
		imagejpeg ( $tmp, $file, 100 );
		return $file;
		/* cleanup memory */
		imagedestroy ( $image );
		imagedestroy ( $tmp );
	}

	public function uploadmulticlienteAction(){
		$previousUrl = $_SESSION['ContainerUrl']['url'];
		$radio = $this->getRequest ()->getPost ( "iradiom" );
			if ($radio == "existente") {
				$archivoid = $this->getRequest ()->getPost ( "marchivo" );
				$archivoconpunto = $this->BuscarRecursoporId ( $archivoid );
				$archivo = substr ( $archivoconpunto, 0, - 1 );
				$anio = $this->getRequest ()->getPost ( "manio" );
				$idruta = $this->getRequest ()->getPost ( "idInformePadre" );
				$tipoarchivo  = $this->getRequest()->getPost("mtipoarchivo");
				$uploaddir ='public/upload/' . $this->CrearRuta ( $idruta );
				// CONSULTAR PARA RETORNAR EL CODIGO DE ESTUDIO
				// $query = $this->entityManager->getRepository ( Recurso::class )->findBy ( array ('idRecursoPadre' => $idruta) );
				// $codestudio = $query[0]->getCodEstudio();
				

					$uploadfile = $uploaddir . $archivo . "/". $tipoarchivo;
					$file = $_FILES ['filemulticliente'] ['name'];
					$extension = pathinfo ( $file, PATHINFO_EXTENSION );

					
					if (! file_exists ( $uploadfile )) {
						mkdir ( $uploadfile, 0777, true );
						chmod ( $uploadfile, 0777 );
					}
					if (move_uploaded_file ( $_FILES ['filemulticliente'] ['tmp_name'], $uploadfile . "/" . $anio . "." . $extension )) {
						if ($archivoid != '-1') {
							$idreturn = $this->AgregarRecursoMulticliente ( $archivoid, $anio, $anio, 'A', $extension, null,null,$tipoarchivo );
							$this->agregarAuditoria($idreturn, 'Subida');
							return	$this->redirect()->toUrl($previousUrl);
						} else {
							return	$this->redirect()->toUrl($previousUrl);
						}
					} else {
						return	$this->redirect()->toUrl($previousUrl);
					}
					return	$this->redirect()->toUrl($previousUrl);
				
	}else {
		$nombrerecurso = $this->getRequest ()->getPost ( "marchivo2" );
		$recurso = $this->ConvertirCadena ( $nombrerecurso, "Normal" );
		$anio = $this->getRequest ()->getPost ( "manio2" );
		$idruta = $this->getRequest ()->getPost ( "idInformePadre" );
		$tipoarchivo  = $this->getRequest()->getPost("mtipoarchivo2");
		$codestudio =$this->getRequest()->getPost("mcodestudio2");
		$uploaddir = 'public/upload/' . $this->CrearRuta ( $idruta );
		
		if ($nombrerecurso <= 5) {
			echo "<script>
				toastr.error('Nombre incorrecto o ya existente.');
					</script>";
		}
		if (! file_exists ($uploaddir . $recurso . "/" . $tipoarchivo)) {
			mkdir ( $uploaddir . $recurso . "/" . $tipoarchivo, 0777, true );
			chmod ( $uploaddir, 0777 );
			$nuevorecurso = $this->AgregarRecursoMulticlienteR ( $idruta, $nombrerecurso, $recurso, 'D', null, 'H',null,$codestudio);
		}
		$uploadfile = $uploaddir . $recurso . "/" . $tipoarchivo;
		$file = $_FILES ['filemulticliente'] ['name']; //
		$extension = pathinfo ( $file, PATHINFO_EXTENSION );
		echo '<pre>';
		if (move_uploaded_file ( $_FILES ['filemulticliente'] ['tmp_name'], $uploadfile . "/" . $anio . "." . $extension )) {
			$idreturn = $this->AgregarRecursoMulticliente ( $nuevorecurso, $anio, $anio, 'A', $extension, null,null,$tipoarchivo );
			$this->agregarAuditoria($idreturn, 'Subida');
			return	$this->redirect()->toUrl($previousUrl);
		} else {
			return	$this->redirect()->toUrl($previousUrl);
		}
		return	$this->redirect()->toUrl($previousUrl);
	}
}

public function AgregarRecursoMulticlienteR($idpadre, $descripcion, $nombre, $tiporecurso, $extensionrecurso, $tipodir, $urlrecurso,$codestudio,$tipoinforme) {
	date_default_timezone_set("America/Lima");
	$fecha = date ( "Y-m-d H:i:s" );
	$recurso = new Recurso ();
	$recurso->setIdRecursoPadre ( $idpadre );
	$recurso->setDescripcionRecurso ( $descripcion );
	$recurso->setRecurso ( $nombre );
	$recurso->setTipoRecurso ( $tiporecurso );
	$recurso->setExtensionRecurso ( $extensionrecurso );
	$recurso->setTipoDirectorio ( $tipodir );
	$recurso->setFechaRecurso ( $fecha );
	$recurso->setUrlRecurso ( $urlrecurso );
	$recurso->setFlag(1);
	$recurso->setCodEstudio($codestudio);
	$recurso->setTipoInforme($tipoinforme);
	$this->entityManager->persist ( $recurso );
	$this->entityManager->flush ();
	$id = $recurso->getIdRecurso();
	return $id;
}
public function AgregarRecursoMulticliente($idpadre, $descripcion, $nombre, $tiporecurso, $extensionrecurso, $tipodir,$codestudio,$tipoinforme) {
	date_default_timezone_set("America/Lima");
	$fecha = date ( "Y-m-d H:i:s" );
	$recurso = new Recurso ();
	$recurso->setIdRecursoPadre ( $idpadre );
	$recurso->setDescripcionRecurso ( $descripcion );
	$recurso->setRecurso ( $nombre );
	$recurso->setTipoRecurso ( $tiporecurso );
	$recurso->setExtensionRecurso ( $extensionrecurso );
	$recurso->setTipoDirectorio ( $tipodir );
	$recurso->setFechaRecurso ( $fecha );
	$recurso->setFlag(1);
	$recurso->setCodEstudio($codestudio);
	$recurso->setTipoInforme($tipoinforme);
	$this->entityManager->persist ( $recurso );
	$this->entityManager->flush ();
	$id = $recurso->getIdRecurso ();
	return $id;
}

public function DescargarRecursoMultiAction() {
	$idRecurso = $this->getEvent ()->getRouteMatch ()->getParam ( 'id' );
	if($idRecurso == -1){
		$this->getResponse ()->setStatusCode ( 500 );
		return;
	}
	$AjaxValidation = $this->getRequest ()->getPost ( 'validar', null );
	$idp = $this->ObtenerIdPadre ( $idRecurso );
	$recurso = $this->entityManager->getRepository ( Recurso::class )->findBy ( array ('idRecurso' => $idp) );
	$tipoDirectorio = $recurso [0]->getTipoDirectorio ();
	if ($tipoDirectorio == 'H') {
		$idP2 = $this->ObtenerIdPadre ( $idp );
		$idPadre = $this->ObtenerIdPadre ( $idP2 );
		$valida = $this->ValidarPermisosDescargar ( $idPadre );
	} else {
		$idPadre = $this->ObtenerIdPadre ( $idp );
		$valida = $this->ValidarPermisosDescargar ( $idPadre );
	}

	$informe = $this->entityManager->getRepository(Recurso::class)->findBy(array('idRecurso' => $idRecurso));
	
	$tipoinforme = $informe[0]->getTipoInforme();
	$informepadre = $informe[0]->getIdRecursoPadre();
	$anioinforme = $informe[0]->getRecurso();
	$extensioninforme = $informe[0]->getExtensionRecurso();

	$filename=$anioinforme.".".$extensioninforme;
	if ($valida == true) {
		$ruta = $this->crearRuta ( $informepadre );

		
	
		$fullPath = 'public/upload/' . $ruta . $tipoinforme;
		$file = $fullPath . "/" . $filename;

	
		if (! file_exists ( $file )) { // file does not exist
			$this->getResponse ()->setStatusCode ( 404 );
			return;
		} else {
			if($AjaxValidation <> 1){
			$this->EnviarEmail($idRecurso);
			$this->agregarAuditoria($idRecurso,'Descarga');
			$fp = fopen ( $file, 'rb' );
			header ( "Cache-Control: public" );
			header ( "Content-Description: File Transfer" );
			header ( 'Content-Type: application/octet-stream' );
			header ( "Content-Disposition: attachment; filename=$filename" );
			header ( 'Expires: 0' );
			header ( 'Cache-Control: must-revalidate' );
			header ( 'Pragma: public' );
			header ( "Content-Length: " . filesize ( $file ) );
			fpassthru ( $fp );
		}
			return $this->response;
		}
	} else
		$this->getResponse ()->setStatusCode ( 404 ); // SI NO TIENE PERMISOS PARA DESCARGAR LE MANDO LA PAGINA DE ERROR
	return;
}


public function EnviarEmail($idRecurso){
	$idrecursopadre = $this->ObtenerIdPadre($idRecurso);
	

	$recursopadre = $this->entityManager->getRepository ( Recurso::class )->findBy ( array ('idRecurso' => $idrecursopadre) );
	$nombrederecursopadre  = $recursopadre[0]->getDescripcionRecurso();

	$recursohijo = $this->entityManager->getRepository ( Recurso::class )->findBy ( array ('idRecurso' => $idRecurso) );
	$nombrerecursohijo = $recursohijo[0]->getDescripcionRecurso();
	$tipoinforme = $recursohijo[0]->getTipoInforme();
	$añoactual  = date('Y');
	$destinatario='felipe.guerrero@ipsos.com';
    $emisor='infonet-support@ipsosperu-online.com';
	$nombre = $_SESSION['ContainerPersonal']['nombre'];
	$apellido = $_SESSION['ContainerPersonal']['apellido'];
    //Enviar email
    $message = new Message();
    $message->addTo($destinatario)
        ->addFrom($emisor)
		->setEncoding("UTF-8");
		
		$headers = $message->getHeaders();
		$headers->removeHeader('Content-Type');
		$headers->addHeaderLine('Content-Type', 'text/html; charset=UTF-8');
		$headers->get('Content-Type')->setEncoding('ASCII');

        $message->setSubject('Aviso de descarga de Multicliente')
		->setBody("
		<div>
		<img align='left' src='http://www.ipsosperu-online.com/infonet_test/public/img/LogoIpsos.png' style='width: 80px; height: 100px;' />
		<img align='right' src='http://www.ipsosperu-online.com/infonet_test/public/img/infonet2.png' style='width: 300px; height: 53px;' /> </div>
		<br/>
	<div style='text-align: center;'>
		<br />
		<div style='text-align: right;'>
			<p style='text-align: center;'>
				&nbsp;</p>
			<p style='text-align: center;'>
				<span style='text-align: center;'>El usuario <strong style='text-align: center;'>".$nombre." ".$apellido."</strong> ha descargado el ".$tipoinforme.": <strong style='color : #009d9c'>'".$nombrederecursopadre." del ".$nombrerecursohijo."'</strong></p>
			<br/>
				<p style='text-align: center;'>
				Sistema de avisos Infonet<br />
				<span style='font-family: &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif; font-size: 14px;'>&copy; Copyright ".$añoactual." Ipsos.com</span></p>
			<p>
				&nbsp;</p>
		</div>
	</div>
		");
	
        // Utilizamos el smtp con nuestras credenciales
        $transport = new SmtpTransport();
        $options   = new SmtpOptions(array(
            'name'  => 'INFONET-Support',
            'host'  => '192.168.184.8',
            'port'  => 25,
            
        ));
        $transport->setOptions($options); //Establecemos la configuración
        $transport->send($message); //Enviamos e
}
}
