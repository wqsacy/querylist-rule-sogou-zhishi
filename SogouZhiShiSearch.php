<?php

	namespace QL\Ext;

	use QL\Contracts\PluginContract;
	use QL\QueryList;

	class SogouZhiShiSearch implements PluginContract
	{

		const API = 'https://www.sogou.com/sogou?';
		const RULES = [
			'title'    => [ 'vrTitle>a' , 'text' ] ,
			'link'     => [ 'vrTitle>a' , 'href' ] ,
			'best_answer' => [ '.best-answer .zs-info:last' , 'text' , '-span' ] ,
			'other_answer' => [ '.js-other-tab-main .zs-info' , 'text' , '-span' ] ,
		];
		const RANGE = '.results .vrwrap';
		protected $ql;
		protected $keyword;
		protected $pageNumber = 10;
		protected $httpOpt = [
			'headers' => [
				'User-Agent'      => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.149 Safari/537.36' ,
				'Accept-Encoding' => 'gzip, deflate, br' ,
			]
		];

		public function __construct ( QueryList $ql , $pageNumber ) {
			$this->ql = $ql->rules( self::RULES )
			               ->range( self::RANGE );
			$this->pageNumber = $pageNumber;
		}

		public static function install ( QueryList $queryList , ...$opt ) {
			$name = $opt[0] ?? 'sogouZhiShiSearch';
			$queryList->bind( $name , function ( $pageNumber = 10 )
			{
				return new SogouZhiShiSearch( $this , $pageNumber );
			} );
		}

		public function setHttpOpt ( array $httpOpt = [] ) {
			$this->httpOpt = $httpOpt;
			return $this;
		}

		public function search ( $keyword ) {
			$this->keyword = $keyword;
			return $this;
		}

		public function page ( $page = 1 , $realURL = false ) {
			return $this->query( $page )
			            ->query()
			            ->getData( function ( $item ) use ( $realURL )
			             {
				             $realURL && $item['link'] = $this->getRealURL( $item['link'] );
				             return $item;
			             } );
		}

		protected function query ( $page = 1 ) {
			$this->ql->get( self::API , [
				'query' => $this->keyword ,
				'ie' => 'utf8',
				'interation' => '196636',
				'page' => $page,
			] , $this->httpOpt );
			return $this->ql;
		}

		/**
		 * 得到百度跳转的真正地址
		 * @param $url
		 * @return mixed
		 */
		protected function getRealURL ( $url ) {
			// 得到搜狗跳转的真正地址
			$header = get_headers( $url , 1 );
			if ( strpos( $header[0] , '301' ) || strpos( $header[0] , '302' ) ) {
				if ( is_array( $header['Location'] ) ) {
					// return $header['Location'][count($header['Location'])-1];
					return $header['Location'][0];
				}
				else {
					return $header['Location'];
				}
			}
			else {
				return $url;
			}
		}

		public function getCountPage () {
			$count = $this->getCount();
			$countPage = ceil( $count / $this->pageNumber );
			return $countPage;
		}

		public function getCount () {
			$count = 0;
			$text = $this->query( 1 )
			             ->find( '.nums' )
			             ->text();
			if ( preg_match( '/[\d,]+/' , $text , $arr ) ) {
				$count = str_replace( ',' , '' , $arr[0] );
			}
			return (int) $count;
		}

	}