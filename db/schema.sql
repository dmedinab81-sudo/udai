-- Schema proporcionado (se mantienen collations y estructura)
-- ----------------------------
DROP TABLE IF EXISTS asignatura;
CREATE TABLE asignatura (
 id int(11) NOT NULL AUTO_INCREMENT,
 nombre varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
 PRIMARY KEY (id) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;
-- ----------------------------
-- Table structure for docente_apoyo
-- ----------------------------
DROP TABLE IF EXISTS docente_apoyo;
CREATE TABLE docente_apoyo (
 id int(11) NOT NULL AUTO_INCREMENT,
 cedula varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
 nombres varchar(120) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
 telefono varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
 correo varchar(120) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
 PRIMARY KEY (id) USING BTREE,
 UNIQUE INDEX cedula(cedula) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;
-- ----------------------------
-- Table structure for docente_tutor
-- ----------------------------
DROP TABLE IF EXISTS docente_tutor;
CREATE TABLE docente_tutor (
 id int(11) NOT NULL AUTO_INCREMENT,
 cedula varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
 nombres varchar(150) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
 telefono varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
 correo varchar(120) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
 PRIMARY KEY (id) USING BTREE,
 UNIQUE INDEX cedula(cedula) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;
-- ----------------------------
-- Table structure for estudiante
-- ----------------------------
DROP TABLE IF EXISTS estudiante;
CREATE TABLE estudiante (
 id int(11) NOT NULL AUTO_INCREMENT,
 tipo_identificacion varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
 identificacion varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
 nombres varchar(150) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
 fecha_nacimiento date NULL DEFAULT NULL,
 edad int(11) NULL DEFAULT NULL,
 nee varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
 tipo_nee varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
 porcentaje_discapacidad varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
 genero varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
 jornada varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
 nivel varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
 grado varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
 PRIMARY KEY (id) USING BTREE,
 UNIQUE INDEX identificacion(identificacion) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;
-- ----------------------------
-- Table structure for estudiante_asignatura
-- ----------------------------
DROP TABLE IF EXISTS estudiante_asignatura;
CREATE TABLE estudiante_asignatura (
 id int(11) NOT NULL AUTO_INCREMENT,
 id_estudiante int(11) NULL DEFAULT NULL,
 id_asignatura int(11) NULL DEFAULT NULL,
 estado varchar(5) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
 PRIMARY KEY (id) USING BTREE,
 INDEX id_estudiante(id_estudiante) USING BTREE,
 INDEX id_asignatura(id_asignatura) USING BTREE,
 CONSTRAINT estudiante_asignatura_ibfk_1 FOREIGN KEY (id_estudiante) REFERENCES estudiante (id) ON DELETE RESTRICT ON UPDATE RESTRICT,
 CONSTRAINT estudiante_asignatura_ibfk_2 FOREIGN KEY (id_asignatura) REFERENCES asignatura (id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;
-- ----------------------------
-- Table structure for institucion
-- ----------------------------
DROP TABLE IF EXISTS institucion;
CREATE TABLE institucion (
 id int(11) NOT NULL AUTO_INCREMENT,
 nombre varchar(150) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
 amie varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
 correo varchar(120) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
 telefono varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
 PRIMARY KEY (id) USING BTREE,
 UNIQUE INDEX amie(amie) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;
-- ----------------------------
-- Table structure for registro_nee
-- ----------------------------
DROP TABLE IF EXISTS registro_nee;
CREATE TABLE registro_nee (
 id int(11) NOT NULL AUTO_INCREMENT,
 id_ubicacion int(11) NULL DEFAULT NULL,
 id_docente_apoyo int(11) NULL DEFAULT NULL,
 id_institucion int(11) NULL DEFAULT NULL,
 id_estudiante int(11) NULL DEFAULT NULL,
 id_docente_tutor int(11) NULL DEFAULT NULL,
 id_representante int(11) NULL DEFAULT NULL,
 enlace_gestion text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
 observaciones text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
 PRIMARY KEY (id) USING BTREE,
 INDEX id_ubicacion(id_ubicacion) USING BTREE,
 INDEX id_docente_apoyo(id_docente_apoyo) USING BTREE,
 INDEX id_institucion(id_institucion) USING BTREE,
 INDEX id_estudiante(id_estudiante) USING BTREE,
 INDEX id_docente_tutor(id_docente_tutor) USING BTREE,
 INDEX id_representante(id_representante) USING BTREE,
 CONSTRAINT registro_nee_ibfk_1 FOREIGN KEY (id_ubicacion) REFERENCES ubicacion (id) ON DELETE RESTRICT ON UPDATE RESTRICT,
 CONSTRAINT registro_nee_ibfk_2 FOREIGN KEY (id_docente_apoyo) REFERENCES docente_apoyo (id) ON DELETE RESTRICT ON UPDATE RESTRICT,
 CONSTRAINT registro_nee_ibfk_3 FOREIGN KEY (id_institucion) REFERENCES institucion (id) ON DELETE RESTRICT ON UPDATE RESTRICT,
 CONSTRAINT registro_nee_ibfk_4 FOREIGN KEY (id_estudiante) REFERENCES estudiante (id) ON DELETE RESTRICT ON UPDATE RESTRICT,
 CONSTRAINT registro_nee_ibfk_5 FOREIGN KEY (id_docente_tutor) REFERENCES docente_tutor (id) ON DELETE RESTRICT ON UPDATE RESTRICT,
 CONSTRAINT registro_nee_ibfk_6 FOREIGN KEY (id_representante) REFERENCES representante_legal (id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;
-- ----------------------------
-- Table structure for representante_legal
-- ----------------------------
DROP TABLE IF EXISTS representante_legal;
CREATE TABLE representante_legal (
 id int(11) NOT NULL AUTO_INCREMENT,
 cedula varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
 nombres varchar(150) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
 telefono varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
 correo varchar(120) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
 PRIMARY KEY (id) USING BTREE,
 UNIQUE INDEX cedula(cedula) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;
-- ----------------------------
-- Table structure for ubicacion
-- ----------------------------
DROP TABLE IF EXISTS ubicacion;
CREATE TABLE ubicacion (
 id int(11) NOT NULL AUTO_INCREMENT,
 mes varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
 zona varchar(10) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
 distrito varchar(10) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
 PRIMARY KEY (id) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;
-- ----------------------------
-- Table structure for usuarios
-- ----------------------------
DROP TABLE IF EXISTS usuarios;
CREATE TABLE usuarios (
 id int(11) NOT NULL AUTO_INCREMENT,
 nombre varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
 email varchar(150) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
 password varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
 rol varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
 creado_en timestamp(0) NOT NULL DEFAULT current_timestamp,
 PRIMARY KEY (id) USING BTREE,
 UNIQUE INDEX email(email) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;