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
use Application\Entity\UsuarioRol;
use Application\Entity\RecursoRol;
use Application\Entity\Auditoria;
class GaleriaIUUController extends AbstractActionController {
	/**
	 * Entity manager.
	 * 
	 * @var Doctrine\ORM\EntityManager
	 */
	private $entityManager;
	public function __construct($entityManager) {
		$this->entityManager = $entityManager;
	}
	public function indexAction() {
		$idtab = $this->getEvent ()->getRouteMatch ()->getParam ( 'idtab' );
		$page = $this->getEvent ()->getRouteMatch ()->getParam ( 'page' );
		$idgaleria = 1589;
		$usuarioAD = $this->identity ();
		$rolusuario = $this->entityManager->getRepository ( UsuarioRol::class )->findOneByidUsuario ( $usuarioAD );
		$rol = $rolusuario->getidRol ();
		$verificarol = $this->entityManager->getRepository ( RecursoRol::class )->findBy ( array ('idRol' => $rol,'idRecurso' => $idgaleria) );
		$descargar = $verificarol [0]->getDescargar ();
		$editar = $verificarol [0]->getEditar ();
		$eliminar = $verificarol [0]->getEliminar ();
		$idpadre = 5644;
		$perpage = 16;
		$ultimoalbum = $this->entityManager->getRepository( Recurso::class)->findBy(array('flag' => 1, 'idRecursoPadre' => 5644),array('FechaRecurso' => 'DESC'),1);
		$idultimoalbum = $ultimoalbum[0]->getidRecurso();
		$ultimacategoria = $this->entityManager->getRepository( Recurso::class)->findBy(array('flag' => 1, 'idRecursoPadre' => $idultimoalbum),array('FechaRecurso' => 'DESC'),1);
		$idultimacategoria = $ultimacategoria[0]->getidRecurso();
		if ($idtab == null) {
			$this->redirect ()->toRoute ( 'galeriaiuu', array ('controller' => 'galeria','action' => 'index','idtab' => $idultimacategoria,'page' => 1 ) );
		}
		if ($page == null) {
			$this->redirect ()->toRoute ( 'galeriaiuu', array ('controller' => 'galeria','action' => 'index','idtab' => $idultimacategoria,'page' => 1 ) );
		}
		$recurso = $this->entityManager->getRepository ( Recurso::class )->findOneByIdRecurso ( $idtab );
		$nombrerecurso = $recurso->getRecurso ();
		$categoria = $this->entityManager->getRepository ( Recurso::class )->findBy ( array ('idRecursoPadre' => $idpadre ), array ('FechaRecurso' => 'DESC' ) );
		$this->agregarAuditoria($idtab, 'Visita');
		if (is_array ( $categoria )) {
			$categorias = $categoria;
		} else
			$categorias [0] = $categoria;
		if ($idtab == null) {
			$idtab = '5645';
		}
		$recurso = $this->entityManager->getRepository ( Recurso::class )->findOneByIdRecurso ( $idtab );
		$nombrerecurso = $recurso->getRecurso ();
		$recursopadre = $this->entityManager->getRepository( Recurso::class) -> findOneByIdRecurso($recurso->getidRecursoPadre());
		$nombrerecursopadre = $recursopadre->getRecurso();
		$ruta = 'public/upload/galeria/galeria_fotos_iuu/' . $nombrerecursopadre. "/".$nombrerecurso;
		$rutalink = $this->CrearRuta ( $idtab );
		$foto = $this->scan_dir ( $ruta );
		$tamaño = count ( $foto );
		$paginas = ceil ( $tamaño / $perpage );
		$page = max ( $page, 1 );
		$page = min ( $page, $paginas );
		$offset = ($page - 1) * $perpage;
		if ($offset < 0)
			$offset = 0;
		$fotopaginada = array_slice ( $foto, $offset, $perpage );
		return new ViewModel ( [ 'paginas' => $paginas,'fotospaginadas' => $fotopaginada,'categorias' => $categoria,'rutalink' => $rutalink,'idtab' => $idtab,'descargar' => $descargar,'editar' => $editar,'eliminar' => $eliminar , $idtab => 'idtab'] );
	}

	public function scan_dir($dir) {
		$ignored = array ('.','..','.svn','.htaccess' 
		);
		$files = array ();
		foreach ( scandir ( $dir,0) as $file ) {
			if (in_array ( $file, $ignored ))
				continue;
			/* $files [$file] = filemtime ( $dir . '/' . $file ); */
				$files [$file] = $file;
		}
	/* 	arsort ( $files ); */
		$files = array_keys ( $files );
		return ($files) ? $files : false;
	}

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

	public function baseUrl() {
		$protocol = isset ( $_SERVER ['HTTPS'] ) ? 'https' : 'http';
		$server = $_SERVER ['HTTP_HOST'];
		$port = $_SERVER ['SERVER_PORT'] != 80 ? ":{$_SERVER['SERVER_PORT']}" : '';
		$path = rtrim ( dirname ( $_SERVER ['SCRIPT_NAME'] ), '/\\' ) . '/';
		return "$protocol://$server$port$path";
	}

	public function crearalbumAction() {
		$nombrealbum = $this->getRequest ()->getPost ( "nombrealbum" );
		$idgaleria = 1589;
		$idAlbum = 5644;
		$fecha = date ( "Y-m-d H:i:s" );
		$albumConvertido = $this->ConvertirCadena ( $nombrealbum, 'Normal' );
		$validarnombre = $this->ValidarNombreExistente ( $albumConvertido, $idAlbum );
		$valida = $this->ValidarPermisosEditar ( $idgaleria );
		if ($valida == true) {
			if ($validarnombre == false) { // Si es true es porque existe un nombre identico
				if (strlen ( $nombrealbum ) <= 5) {
					$this->getResponse ()->setStatusCode ( 404 ); // Nombre muy corto
					return;
				}
				$uploaddir = 'public/upload/' . $this->CrearRuta ( $idAlbum ) . $albumConvertido;
				mkdir ( $uploaddir, 0777, true );
				$recurso = new Recurso ();
				$recurso->setIdRecursoPadre ( $idAlbum );
				$recurso->setDescripcionRecurso ( $nombrealbum );
				$recurso->setRecurso ( $this->ConvertirCadena ( $nombrealbum, 'Normal' ) );
				$recurso->setTipoRecurso ( 'D' );
				$recurso->setTipoDirectorio ( 'N' );
				$recurso->setFechaRecurso ( $fecha );
				$recurso->setFlag(1);
				$this->entityManager->persist ( $recurso );
				$this->entityManager->flush ();
				return $this->response;

			} else {
				echo 'nombre identico';
				$this->getResponse ()->setStatusCode ( 404 ); // NOMBRE IDENTICO
				return;
			}
		} else {
			echo 'No hay permisos';
			$this->getResponse ()->setStatusCode ( 404 ); // SI NO TIENE PERMISOS DE EDICION
			return;
		}
	}

	public function subirfotosAction() {
		$idruta = $this->getRequest ()->getPost ( "idtab" );
		$uploaddir = 'public/upload/' . $this->CrearRuta ( $idruta );
		$nombreconvertido = $this->ConvertirCadena ( $_FILES ['file'] ['name'], 'Archivo' );
		$uploadfile = $uploaddir . $nombreconvertido;
		$file = $_FILES ['file'] ['name'];
		$filename = $_FILES ['filename'];
		$extension = end ( explode ( '.', $file ) );
		$recurso = explode ( '.', $file );
		if (count ( $recurso ) > 2) {
			$recursoext = array_pop ( $recurso );
		}
		$recurso = implode ( array_slice ( $recurso, - 2, 1, FALSE ) );
		$nombre = $file;
		$nombre = explode ( '.', $file );
		if (count ( $nombre ) > 2) {
			$nombreext = array_pop ( $nombre );
		}
		$nombre = implode ( array_slice ( $nombre, - 2, 1, TRUE ) );
		$recurso = $this->ConvertirCadena ( $recurso, 'Subida' );
		if (! file_exists ( $uploaddir )) {
			mkdir ( $uploaddir, 0777, true );
		}
		echo '<pre>';
		if (move_uploaded_file ( $_FILES ['file'] ['tmp_name'], $uploadfile )) {
			echo "File is valid, and was successfully uploaded.\n";
			echo $ruta;
			$this->AgregarRecurso ( $idruta, $recurso, $recurso, 'A', strtolower ( $extension ), null );
			$this->redirect ()->toRoute ( 'galeria' );
			
			echo "Archivo Registrado en Base de Datos";
		} else {
			echo "Possible file upload attack!\n";
			echo $ruta;
		}
		$this->redirect ()->toRoute ( 'galeria' );
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
	
	// Funcion que recibe una cadena y la convierte a caracteres válidos y en minuscula
	public function ConvertirCadena($string, $tipo) {
		$string = trim ( $string );
		$string = strtolower ( $string );
		$string = str_replace ( array ('á','à','ä','â','ª','Á','À','Â','Ä' 
		), array ('a','a','a','a','a','A','A','A','A' 
		), $string );
		$string = str_replace ( array ('é','è','ë','ê','É','È','Ê','Ë' 
		), array ('e','e','e','e','E','E','E','E' 
		), $string );
		$string = str_replace ( array ('í','ì','ï','î','Í','Ì','Ï','Î' 
		), array ('i','i','i','i','I','I','I','I' 
		), $string );
		$string = str_replace ( array ('ó','ò','ö','ô','Ó','Ò','Ö','Ô' 
		), array ('o','o','o','o','O','O','O','O' 
		), $string );
		$string = str_replace ( array ('ú','ù','ü','û','Ú','Ù','Û','Ü' 
		), array ('u','u','u','u','U','U','U','U' 
		), $string );
		$string = str_replace ( array ('ñ','Ñ','ç','Ç' 
		), array ('n','N','c','C' 
		), $string );
		if ($tipo == 'Normal') {
			$string = str_replace ( array ("\\","¨","º","-","~","#","@","|","!","\"","·","$","%","&","/","?","'","¡","¿","[","^","`","]","+","}","{","¨","´",">","< ",";",",",":","." ), '', $string );
		} else {
			$string = str_replace ( array ("\\","¨","º","-","~","#","@","|","!","\"","$","%","&","/","?","'","¡","¿","[","^","`","]","+","}","{","¨","´",">","< ",";",",",":" ), '', $string );
		}
		$string = str_replace ( array ('(',')',' ' ), array ('-','-','_'), $string );
		return strtolower ( $string );
	}

	public function ValidarPermisosEditar($idtab) {
		$usuarioAD = $this->identity ();
		$rolusuario = $this->entityManager->getRepository ( UsuarioRol::class )->findOneByidUsuario ( $usuarioAD );
		$rol = $rolusuario->getidRol ();
		$verificarol = $this->entityManager->getRepository ( RecursoRol::class )->findBy ( array ('idRol' => $rol,'idRecurso' => $idtab) );
		$editar = $verificarol [0]->getEditar ();
		return $editar;
	}
	
	public function EliminarFotoAction(){
		$ruta = $this->getRequest ()->getPost ( "Ruta" );
		$uploaddir = 'public/upload/' . $ruta;
		if (! unlink ( $uploaddir )) {
			return;
		} else {
			echo ("Archivo borrado en ruta :". $uploaddir);
			return $this->response;
		}
	}
	
	public function CargarhijosAction(){
		$idrecurso = $this->getRequest ()->getPost ( "idRecurso" );
		$fullurl = $this->getRequest()->getPost('Url');
		$subcategoria = $this->entityManager->getRepository ( Recurso::class )->findBy ( array ('idRecursoPadre' => $idrecurso ));
		if($subcategoria == null)
		{
			$cadena .= "<h5> No hay elementos</h5>";
			$this->response->setContent ( $cadena );
			return $this->response;
		}
		else{
		if (is_array ( $subcategoria )) {
			$subcategorias = $subcategoria;
		} else
			$subcategorias [0] = $subcategoria;
			$cadena = "";
			foreach ( $subcategorias as $subcategoria ) {
		$cadena .= "<a href='".$fullurl."/galeriaiuu/".$subcategoria->getidRecurso()."/1'>".$subcategoria->getDescripcionRecurso()."</a><br>";
			}
		$this->response->setContent ( $cadena );
		return $this->response;
	}
	}
	
	public function crearSubCategoriaAction(){
		
		$nombresubcategoria= $this->getRequest ()->getPost ( "nombresubcategoria" );
			$idcategoria = $this->getRequest()->getPost("listacategorias");
				$idtab = explode('/',$fullurl);
		$idgaleria = 1589;
		$idAlbum = 5644;
		$fecha = date ( "Y-m-d H:i:s" );
		$albumConvertido = $this->ConvertirCadena ( $nombresubcategoria, 'Normal' );
		$validarnombre = $this->ValidarNombreExistente ( $albumConvertido, $idcategoria );
		$valida = $this->ValidarPermisosEditar ( $idgaleria );
		if ($valida == true) {
			if ($validarnombre == false) { // Si es true es porque existe un nombre identico
				if (strlen ( $nombresubcategoria ) <= 4) {
					$this->getResponse ()->setStatusCode ( 404 ); // Nombre muy corto
					return;
				}
				$uploaddir = 'public/upload/' . $this->CrearRuta ( $idcategoria ) . $albumConvertido;
				mkdir ( $uploaddir, 0777, true );
				$recurso = new Recurso ();
				$recurso->setIdRecursoPadre ( $idcategoria );
				$recurso->setDescripcionRecurso ( $nombresubcategoria );
				$recurso->setRecurso ( $this->ConvertirCadena ( $nombresubcategoria, 'Normal' ) );
				$recurso->setTipoRecurso ( 'D' );
				$recurso->setTipoDirectorio ( 'N' );
				$recurso->setFechaRecurso ( $fecha );
				$recurso->setFlag(1);
				$this->entityManager->persist ( $recurso );
				$this->entityManager->flush ();
				return $this->response;
			} else {
				echo 'Nombre idéntico';
				$this->getResponse ()->setStatusCode ( 404 ); // NOMBRE IDENTICO
				return;
			}
		} else {
			echo 'No hay permisos';
			$this->getResponse ()->setStatusCode ( 404 ); // SI NO TIENE PERMISOS DE EDICION
			return;
		}
	}

	public function ListarCategoriasAction(){
		$categorias = $this->entityManager->getRepository ( Recurso::class )->findBy ( array ('idRecursoPadre' => 5644 ));
		$cadena = "";
		foreach ($categorias as $categoria)
		{
			$cadena .= "<option value='".$categoria->getidRecurso()."'>".$categoria->getDescripcionRecurso()."</option>";
		}
		$this->response->setContent ( $cadena );
		return $this->response;
	}

	public function agregarAuditoria($idRecurso,$Accion){
		$usuarioAD = $this->identity ();
		$fecha = date ( "Y-m-d H:i:s" );
		$Auditoria = new Auditoria ();
		$Auditoria->setidRecurso ( $idRecurso );
		$Auditoria->setidUsuario ( $usuarioAD );
		$Auditoria->setAccion ( $Accion );
		$Auditoria->setFecha ( $fecha );
		$this->entityManager->persist ( $Auditoria );
		$this->entityManager->flush ();
	}

}
