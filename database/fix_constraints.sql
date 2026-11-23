USE `db_pengaduan_smkn1cipeundeuy`;
SET FOREIGN_KEY_CHECKS = 0;

-- Drop foreign keys with duplicate/generic numbers if they exist
ALTER TABLE `pengaduan` DROP FOREIGN KEY IF EXISTS `1`;
ALTER TABLE `pengaduan` DROP FOREIGN KEY IF EXISTS `2`;
ALTER TABLE `pengaduan` DROP FOREIGN KEY IF EXISTS `fk_pengaduan_user`;
ALTER TABLE `pengaduan` DROP FOREIGN KEY IF EXISTS `fk_pengaduan_kategori`;

ALTER TABLE `tindak_lanjut` DROP FOREIGN KEY IF EXISTS `1`;
ALTER TABLE `tindak_lanjut` DROP FOREIGN KEY IF EXISTS `2`;
ALTER TABLE `tindak_lanjut` DROP FOREIGN KEY IF EXISTS `fk_tl_pengaduan`;
ALTER TABLE `tindak_lanjut` DROP FOREIGN KEY IF EXISTS `fk_tl_petugas`;

-- Add foreign keys with unique, standardized names
ALTER TABLE `pengaduan`
  ADD CONSTRAINT `fk_pengaduan_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pengaduan_kategori` FOREIGN KEY (`kategori_id`) REFERENCES `kategori_pengaduan` (`id`) ON DELETE SET NULL;

ALTER TABLE `tindak_lanjut`
  ADD CONSTRAINT `fk_tl_pengaduan` FOREIGN KEY (`pengaduan_id`) REFERENCES `pengaduan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_tl_petugas` FOREIGN KEY (`petugas_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

SET FOREIGN_KEY_CHECKS = 1;
