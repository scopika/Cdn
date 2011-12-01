<?php
include_once(realpath(dirname(__FILE__)) . '/../../../classes/PluginsTransports.class.php');

class Cdn extends PluginsClassiques {
    
    // liste des CDN
    private $_cdn = array(
    	'img' => array(
            'img1.static.istyl.com',
            'img2.static.istyl.com',
    		'img3.static.istyl.com',
        ),
        'css' => array(
            'assets.static.istyl.com'
        ),
        'js' => array(
        	'assets.static.istyl.com'
        )
    );

    /**
     * Remplacement de l'URL des images en prenant en compte les CDN 
     * @see PluginsClassiques::post()
     */
	function post() {
	    global $res;
	    
	    // Images dans /client/cache/xxx/... (seulement les images dont l'adresse est relative)
	    $res = preg_replace_callback(
	    	",[[:space:]](src|href)=[\"\']([^http]*)(client\/cache\/([^\"\'\/]*)\/([^\"\']*))[\"\'],", 
	    	"Cdn::_replaceImg", 
	    	$res
	    );
	    
	    // CSS
	    $css = preg_match_all('/<link[^>]*(type=\"text\/css\")*>/', $res, $matches);
	    foreach((array) $matches as $match) {
	        $newCss = preg_replace_callback(
    	    	",[[:space:]]href=[\"\']([^\"\']*)[\"\'],", 
    	    	"Cdn::_replaceCss", 
    	    	$match,
    	    	1
    	    );
    	    $res = str_replace($match, $newCss, $res);
	    }
	    
	    // JS
	    $css = preg_match_all('/<script[^>]*(type=\"text\/javascript\")*>/', $res, $matches);
	    foreach((array) $matches as $match) {
	        $newJs = preg_replace_callback(
    	    	",[[:space:]]src=[\"\']([^\"\']*)[\"\'],", 
    	    	"Cdn::_replaceJs", 
    	    	$match,
    	    	1
    	    );
    	    $res = str_replace($match, $newJs, $res);
	    }
	}

	/**
	 * CDN pour les CSS
	 * @param unknown_type $matches
	 */
	private function _replaceCss($matches) {
	    $url = $this->_replaceWithCdn('css', $matches[1]);
	    return ' href="'.$url.'"';
	}
	
	/**
	 * CDN pour les JS
	 * @param unknown_type $matches
	 */
	private function _replaceJs($matches) {
	    $url = $this->_replaceWithCdn('js', $matches[1]);
	    return ' src="'.$url.'"';
	}
	
	/**
	 * CDN pour les images
	 * @param unknown_type $matches
	 */
	private function _replaceImg($matches) {
	    $url = $this->_replaceWithCdn('img', $matches[3]);
	    return ' ' . $matches[1] . '="' . $url . '"';
	}
	
	/**
	 * Fonction de remplacment des URL en prenant en compte les CDN
	 * @param string $type : type de fichier
	 * @param string $url : url du fichier
	 */
	private function _replaceWithCdn($type, $url) {
	    // URL absolue => stop
	    if(substr($url, 0, 4) == 'http') return $url;
	    
	    if(empty($this->_cdn[$type])) return $url;	    
	    $totalCDN = count($this->_cdn[$type]);
	    if($totalCDN <= 0) return $url;	    
	    
	    // On gère max 16 CDN
	    if($totalCDN > 16) $totalCDN = 16;
	    
	    // L'index du CDN à utiliser. Un fichier aura toujours le même CDN
	    // (on peut ainsi utiliser le cache navigateur)
	    $cdnIndex = hexdec(substr(md5($url), 0, 1)) % $totalCDN;
	    
	    // protocole
        $protocole = $_SERVER['HTTPS'] ? 'https' : 'http';
        $protocole .= '://';
        
        $url =  $protocole . $this->_cdn[$type][$cdnIndex] . '/' . $url;
        return $url;
	}
}