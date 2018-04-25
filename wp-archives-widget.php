<?php
/*
Plugin Name: wp-archives-widget
Plugin URI: https://github.com/ledyba/wp-archives-widget
Description: Archives widget that groups by year and month
Version: 1.0
Author: Paul de Wouters + PSI
License: GPL3
*/

/*  Copyright 2015  Paul de Wouters

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class wp_archives_widget extends WP_Widget {

	function __construct() {
		$widget_ops = array(
			'classname'   => 'wp_archives_widget',
			'description' => __( 'Display links to archives grouped by year then month.', 'wp_archives_widget' ),
		);
		parent::__construct( 'baw_widgetarchives_widget_my_archives', __( 'Archives Widget', 'wp_archives_widget' ), $widget_ops );
	}

	//build the widget settings form
	function form( $instance ) {
		$defaults = array( 'title' => 'archives' );
		$instance = wp_parse_args( (array) $instance, $defaults );
		$title    = $instance['title'];

		?>
		<p><?php esc_html_e( 'Title:', 'wp-archives-widget' ); ?>
			<input class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<?php
	}

	//save the widget settings
	function update( $new_instance, $old_instance ) {
		$instance          = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );

		return $instance;
	}

	//display the widget
	function widget( $args, $instance ) {
		extract( $args );

		echo $before_widget;
		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? __( 'Archives', 'better-archives-widget' ) : $instance['title'], $instance, $this->id_base );


		if ( ! empty( $title ) ) {
			echo $before_title . $title . $after_title;
		};

		// years - months

		global $wpdb;
		$prevYear    = '';
		$currentYear = '';

		/**
		 * Filter the SQL WHERE clause for retrieving archives.
		 */
		$where = apply_filters( 'getarchives_where', "WHERE post_type = 'post' AND post_status = 'publish' AND post_date <= now()" );

		/**
		 * Filter the SQL JOIN clause for retrieving archives.
		 */
		$join = apply_filters( 'getarchives_join', '' );
		?>
		<style>
		.wp-archives-widget-years li {
			border: none;
		}
		.wp-archives-widget-years .toggle {
			cursor: pointer;
		}
		.wp-archives-widget-years .wp-archives-widget-months.hidden {
			visibility: none;
		}
		.wp-archives-widget-years .widget ul.wp-archives-widget-months {
			visibility: block;
		}
		</style>
		<script>
		document.addEventListener("DOMContentLoaded", function(event) {
			document.querySelectorAll('.widget .wp-archives-widget-year').forEach(function(it) {
				var btn = it.querySelector('.toggle');
				var list = it.querySelector('ul');
				btn.addEventListener('click', function() {
					if(list.classList.toggle('hidden')) {
						btn.textContent='➡️';
					}else{
						btn.textContent='⬇️';
					}
				});
			});
		});
		</script>
		<?php

		if ( $months = $wpdb->get_results( "SELECT YEAR(post_date) AS year, MONTH(post_date) AS numMonth, DATE_FORMAT(post_date, '%Y/%m') AS date_string, count(ID) as post_count FROM $wpdb->posts $join $where GROUP BY YEAR(post_date), MONTH(post_date) ORDER BY post_date DESC" ) ) {
			echo '<ul class="wp-archives-widget-years">';
			$postsInYear = array();
			foreach ( $months as $month ) {
				$postsInYear[$month->year] += $month->post_count;
			}
			foreach ( $months as $month ) {
				$currentYear = $month->year;
				if ( ( $currentYear !== $prevYear ) && ( '' !== $prevYear ) ) {
					echo '</ul></li>';
				}
				if ( $currentYear !== $prevYear ) {
					?>
					<li class="wp-archives-widget-year">
					<span class="toggle">➡️</span>
					<a href="<?php echo esc_url( get_year_link( $month->year ) ); ?>"><?php echo esc_html( $month->year.'('.$postsInYear[$month->year].')' ); ?></a>
					<ul class="wp-archives-widget-months hidden">
					<?php
				} ?>
				<li class="wp-archives-widget-month">
					<a href="<?php echo esc_url( get_month_link( $month->year, $month->numMonth ) ); ?>"><?php echo esc_html( $month->date_string.'('.$month->post_count.')' ); ?></a>
				</li>
				<?php
				$prevYear = $month->year;
			}
		}
		?>
		</ul></li>
		<?php
		echo '</ul>';
		echo $after_widget;
	}
}

add_action('widgets_init', create_function('', 'return register_widget("wp_archives_widget");'));
