<?php

namespace ionmvc\packages\html\classes;

use ionmvc\exceptions\app as app_exception;

class tag {

	const overwrite = 1;
	const append    = 2;
	const prepend   = 3;
	const remove    = 4;

	private $tag;
	private $attrs = [];
	private $singleton_tags = ['area','base','br','col','command','embed','hr','img','input','link','meta','param','source'];
	private $singleton = false;
	private $value = '';
	private $children = [];

	public function __construct( $tag,$singleton=null ) {
		$this->tag = $tag;
		$this->singleton = ( !is_null( $singleton ) ? $singleton : in_array( $tag,$this->singleton_tags ) );
	}

	public function __call( $method,$args ) {
		switch( $method ) {
			case 'class_add':
				return $this->_class( $args[0],self::append );
				break;
			case 'class_remove':
				return $this->_class( $args[0],self::remove );
				break;
			case 'select':
			case 'selected':
				$this->attrs['selected'] = 'selected';
				break;
			case 'deselect':
				if ( isset( $this->attrs['selected'] ) ) {
					unset( $this->attrs['selected'] );
				}
				break;
			case 'check':
			case 'checked':
				$this->attrs['checked'] = 'checked';
				break;
			case 'uncheck':
				if ( isset( $this->attrs['checked'] ) ) {
					unset( $this->attrs['checked'] );
				}
				break;
			default:
				$this->attrs[$method] = $args[0];
				break;
		}
		return $this;
	}

	public function __isset( $key ) {
		return isset( $this->attrs[$key] );
	}

	public function __get( $key ) {
		if ( isset( $this->attrs[$key] ) ) {
			$attr = $this->attrs[$key];
			switch( $key ) {
				case 'class':
					$attr = implode( ' ',$attr );
				break;
			}
			return $attr;
		}
		return false;
	}

	public function _class( $name,$type=self::overwrite ) {
		if ( !isset( $this->attrs['class'] ) ) {
			$this->attrs['class'] = [];
		}
		elseif ( is_string( $this->attrs['class'] ) ) {
			$this->attrs['class'] = explode( ' ',$this->attrs['class'] );
		}
		switch( $type ) {
			case self::append:
				$this->attrs['class'][] = $name;
				break;
			case self::prepend:
				array_unshift( $this->attrs['class'],$name );
				break;
			case self::overwrite:
				$this->attrs['class'] = [ $name ];
				break;
			case self::remove:
				if ( ( $idx = array_search( $name,$this->attrs['class'] ) ) !== false ) {
					unset( $this->attrs['class'][$idx] );
				}
				break;
			default:
				throw new app_exception('Invalid type');
				break;
		}
		return $this;
	}

	public function has_attr( $name ) {
		return isset( $this->attrs[$name] );
	}

	public function attr( $name,$value ) {
		$this->attrs[$name] = $value;
		return $this;
	}

	public function attrs( $data ) {
		$this->attrs = array_merge( $this->attrs,$data );
		return $this;
	}

	public function remove_attr( $name ) {
		if ( isset( $this->attrs[$name] ) ) {
			unset( $this->attrs[$name] );
		}
		return $this;
	}

	public function data( $name,$value ) {
		return $this->attr( "data-{$name}",$value );
	}

	public function inner_content( $data,$action=self::append ) {
		if ( $this->singleton ) {
			throw new app_exception('Value not allowed for singleton tags');
		}
		switch( $action ) {
			case self::prepend:
				$this->value = $data . $this->value;
				break;
			case self::append:
				$this->value .= $data;
				break;
			case self::overwrite:
				$this->value = $data;
				break;
			default:
				throw new app_exception('Invalid action');
				break;
		}
		return $this;
	}

	public function child_add( tag $tag ) {
		$this->children[] = $tag;
		return $this;
	}

	public function render_start() {
		$attrs = $this->attrs;
		if ( isset( $attrs['class'] ) && is_array( $attrs['class'] ) ) {
			$attrs['class'] = implode( ' ',$attrs['class'] );
		}
		return "<{$this->tag}" . ( count( $attrs ) > 0 ? self::build_attrs( $attrs,null,true ) : '' ) . ( $this->singleton ? ' /' : '' ) . '>';
	}

	public function render_inner( $data=null ) {
		if ( $this->singleton ) {
			return '';
		}
		if ( count( $this->children ) > 0 ) {
			$data = '';
			foreach( $this->children as $child ) {
				$data .= $child->render();
			}
		}
		elseif ( is_null( $data ) ) {
			$data = $this->value;
		}
		elseif ( $data instanceof \Closure ) {
			$data = call_user_func( $data );
		}
		else {
			$class = __CLASS__;
			if ( $data instanceof $class ) {
				$data = $data->render();
			}
		}
		return $data;
	}

	public function render_end() {
		if ( $this->singleton ) {
			return '';
		}
		return "</{$this->tag}>";
	}

	public function render( $data=null ) {
		return $this->render_start() . $this->render_inner( $data ) . $this->render_end();
	}

	public static function build_attrs( $data,$allow=null,$bypass=false,$default=null ) {
		if ( !is_array( $data ) ) {
			throw new app_exception('Array is required');
		}
		if ( isset( $config['default'] ) ) {
			$data = array_merge( $default,$data );
		}
		if ( isset( $config['allowed'] ) ) {
			$data_attrs = in_array( 'data-*',$config['allowed'] );
		}
		$attrs = [];
		foreach( $data as $attr => $value ) {
			if ( isset( $config['allowed'] ) ) {
				if ( strpos( $attr,'data-' ) === 0 ) {
					if ( !$data_attrs && !in_array( $attr,$config['allowed'] ) ) {
						continue;
					}
				}
				elseif ( !in_array( $attr,$config['allowed'] ) ) {
					continue;
				}
			}
			if ( $attr == 'value' || $value !== '' ) {
				$attrs[$attr] = $value;
			}
		}
		$str = '';
		foreach( $attrs as $attr => $value ) {
			$str .= " {$attr}=\"{$value}\"";	
		}
		return $str;
	}

	public static function create( $tag,$singleton=null ) {
		return new self( $tag,$singleton );
	}

}

?>