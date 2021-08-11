<?php

class Liquid2CouponDb extends Liquid2CouponDbAbstract {

	/**
	 * Get things started
	 *
	 * @access  public
	 * @since   1.0
	 */
	public function __construct() {

		global $wpdb;

		$this->table_name  = $wpdb->prefix . 'liquid2coupons';
		$this->primary_key = 'id';
		$this->version     = '1.0';

	}

	/**
	 * Get columns and formats
	 *
	 * @access  public
	 * @since   1.0
	 */
	public function get_columns() {
		return array(
			'id'          => '%d',
			'customer_id' => '%d',
			'asset_id'    => '%s',
			'quantity'    => '%d',
			'invoice_id'  => '%s',
			'coupon_code'  => '%s',
			'coupon_id'  => '%d',
			'status'      => '%s', // new, unpaid, paid, complete
			'created'     => '%s',
			'modified'    => '%s',
		);
	}

	/**
	 * Get default column values
	 *
	 * @access  public
	 * @since   1.0
	 */
	public function get_column_defaults() {
		return array(
			'customer_id' => 0,
			'asset_id'    => '',
			'quantity'    => '',
			'invoice_id'  => '',
			'coupon_code'  => '',
			'coupon_id'  => '',
			'status'      => '',
			'created'     => date( 'Y-m-d H:i:s' ),
			'modified'    => date( 'Y-m-d H:i:s' ),
		);
	}

	/**
	 * Retrieve orders from the database
	 *
	 * @access  public
	 *
	 * @param array $args
	 * @param bool $count Return only the total number of results found (optional)
	 *
	 * @since   1.0
	 */
	public function get_redemptions( $args = array(), $count = false ) {

		global $wpdb;

		$defaults = array(
			'number'     => 20,
			'offset'     => 0,
			'id'         => 0,
			'status'     => '',
			'invoice_id' => '',
			'orderby'    => 'id',
			'order'      => 'DESC',
		);

		$args = wp_parse_args( $args, $defaults );

		if ( $args['number'] < 1 ) {
			$args['number'] = 999999999999;
		}

		$where = '';

		// specific referrals
		if ( ! empty( $args['id'] ) ) {
			if ( is_array( $args['id'] ) ) {
				$ids = implode( ',', $args['id'] );
			} else {
				$ids = intval( $args['id'] );
			}

			$where .= "WHERE `id` IN( {$ids} ) ";
		}

		if ( ! empty( $args['status'] ) ) {
			if ( empty( $where ) ) {
				$where .= " WHERE";
			} else {
				$where .= " AND";
			}

			if ( is_array( $args['status'] ) ) {
				$where .= " `status` IN('" . implode( "','", $args['status'] ) . "') ";
			} else {
				$where .= " `status` = '" . $args['status'] . "' ";
			}
		}

		if ( ! empty( $args['customer_id'] ) ) {
			if ( empty( $where ) ) {
				$where .= " WHERE";
			} else {
				$where .= " AND";
			}

			if ( is_array( $args['customer_id'] ) ) {
				$where .= " `customer_id` IN('" . implode( "','", $args['customer_id'] ) . "') ";
			} else {
				$where .= " `customer_id` = '" . $args['customer_id'] . "' ";
			}
		}

		if ( ! empty( $args['invoice_id'] ) ) {
			if ( empty( $where ) ) {
				$where .= " WHERE";
			} else {
				$where .= " AND";
			}

			if ( is_array( $args['invoice_id'] ) ) {
				$where .= " `invoice_id` IN(" . implode( ',', $args['invoice_id'] ) . ") ";
			} else {
				if ( ! empty( $args['search'] ) ) {
					$where .= " `invoice_id` LIKE '%%" . $args['invoice_id'] . "%%' ";
				} else {
					$where .= " `invoice_id` = '" . $args['invoice_id'] . "' ";
				}
			}
		}

		if ( ! empty( $args['created'] ) ) {
			if ( is_array( $args['created'] ) ) {
				if ( ! empty( $args['created']['start'] ) ) {
					if ( false !== strpos( $args['created']['start'], ':' ) ) {
						$format = 'Y-m-d H:i:s';
					} else {
						$format = 'Y-m-d 00:00:00';
					}

					$start = date( $format, strtotime( $args['created']['start'] ) );

					if ( ! empty( $where ) ) {
						$where .= " AND `date` >= '{$start}'";
					} else {
						$where .= " WHERE `created` >= '{$start}'";
					}
				}

				if ( ! empty( $args['created']['end'] ) ) {
					if ( false !== strpos( $args['created']['end'], ':' ) ) {
						$format = 'Y-m-d H:i:s';
					} else {
						$format = 'Y-m-d 23:59:59';
					}

					$end = date( $format, strtotime( $args['created']['end'] ) );

					if ( ! empty( $where ) ) {
						$where .= " AND `created` <= '{$end}'";
					} else {
						$where .= " WHERE `created` <= '{$end}'";
					}
				}

			} else {

				$year  = date( 'Y', strtotime( $args['created'] ) );
				$month = date( 'm', strtotime( $args['created'] ) );
				$day   = date( 'd', strtotime( $args['created'] ) );

				if ( empty( $where ) ) {
					$where .= " WHERE";
				} else {
					$where .= " AND";
				}

				$where .= " $year = YEAR ( created ) AND $month = MONTH ( created ) AND $day = DAY ( created )";
			}
		}

		// todo fix with other, e.g. data?
		$args['orderby'] = ! array_key_exists( $args['orderby'], $this->get_columns() ) ? $this->primary_key : $args['orderby'];

		$cache_key = ( true === $count ) ? md5( 'la2c_count' . serialize( $args ) ) : md5( 'la2c_results_' . serialize( $args ) );

		$results = wp_cache_get( $cache_key, 'la2c' );

		if ( false === $results ) {

			if ( true === $count ) {

				$results = absint( $wpdb->get_var( "SELECT COUNT({$this->primary_key}) FROM {$this->table_name} {$where};" ) );

			} else {

				$results = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT * FROM {$this->table_name} {$where} ORDER BY {$args['orderby']} {$args['order']} LIMIT %d, %d;",
						absint( $args['offset'] ),
						absint( $args['number'] )
					)
				);

			}

			wp_cache_set( $cache_key, $results, 'la2c', 3600 );

		}

		return $results;

	}

	/**
	 * Return the number of results found for a given query
	 *
	 * @param array $args
	 *
	 * @return int
	 */
	public function count( $args = array() ) {
		return $this->get_redemptions( $args, true );
	}

	/**
	 * Create the table
	 *
	 * @access  public
	 * @since   1.0
	 */
	public function create_table() {

		global $wpdb;

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$sql = "CREATE TABLE " . $this->table_name . " (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		customer_id bigint(20) NOT NULL,
		asset_id text NOT NULL,
		quantity int NOT NULL,
		invoice_id text NULL,
		coupon_code text NULL,
		coupon_id bigint(20) NULL,
		status varchar(30) NOT NULL,
		created datetime NOT NULL,
		modified datetime NOT NULL,
		PRIMARY KEY  (id)
		) CHARACTER SET utf8 COLLATE utf8_general_ci;";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version );
	}
}
