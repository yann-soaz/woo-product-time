<?php

function get_limit_date_product ($id) {
  $value = get_post_meta( $id, YsProductMeta::$meta_name, true );
  return (empty($value)) ? false : $value;
}

function is_max_sell_date_over ($id) {
  if ($date = get_limit_date_product($id)) {
    return date('Y-m-d') > $date;
  }
  return false;
}

function is_this_date_over ($date) {
  return date('Y-m-d') > $date;
}

function format_the_date ($date) {
  $format = get_option('date_format');
  return date_i18n($format, strtotime($date));
}