SET FOREIGN_KEY_CHECKS=0;


DROP TABLE IF EXISTS /*TABLE_PREFIX*/t_trd_translation;
CREATE TABLE /*TABLE_PREFIX*/t_trd_translation (
  pk_i_id INT NOT NULL AUTO_INCREMENT,
  fk_c_locale_code CHAR(5) NOT NULL,
  s_original_text TEXT,
  s_translated_text TEXT,
  dt_create_date TIMESTAMP,

  PRIMARY KEY (pk_i_id)
) ENGINE=InnoDB DEFAULT CHARACTER SET 'UTF8' COLLATE 'UTF8_GENERAL_CI';



SET FOREIGN_KEY_CHECKS=1;