CREATE TABLE areaatuacao
(
    id BIGINT(20) unsigned PRIMARY KEY NOT NULL AUTO_INCREMENT,
    titulo VARCHAR(300),
    texto VARCHAR(500),
    status CHAR(1) DEFAULT 'A'
);
CREATE UNIQUE INDEX id ON areaatuacao (id);
CREATE TABLE artigo
(
    id INT(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
    titulo VARCHAR(100) NOT NULL,
    resumo VARCHAR(250) NOT NULL,
    arquivo VARCHAR(100) NOT NULL,
    url VARCHAR(255) NOT NULL,
    publico TINYINT(1) DEFAULT '0',
    userid INT(11) NOT NULL,
    datacriacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    link VARCHAR(255),
    tipoartigo CHAR(1) DEFAULT 'A',
    texto TEXT,
    CONSTRAINT artigo_ibfk_1 FOREIGN KEY (userid) REFERENCES usuario (id)
);
CREATE INDEX userid ON artigo (userid);
CREATE TABLE carrossel
(
    bcrid INT(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
    bcrimg VARCHAR(300) NOT NULL,
    bcrprincipal TINYINT(1) DEFAULT '0',
    bcrtitulo VARCHAR(300),
    bcrsubtitulo VARCHAR(300),
    bcrtembotao TINYINT(1) DEFAULT '0',
    bcrtxtbotao VARCHAR(100),
    bcrfuncbotao VARCHAR(400)
);
CREATE TABLE idioma
(
    id INT(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    imagem VARCHAR(255) NOT NULL
);
CREATE TABLE idiomatime
(
    ididioma INT(11) NOT NULL,
    idtime INT(11) NOT NULL,
    CONSTRAINT `PRIMARY` PRIMARY KEY (ididioma, idtime),
    CONSTRAINT fk_idioma FOREIGN KEY (ididioma) REFERENCES idioma (id),
    CONSTRAINT fk_nossotime FOREIGN KEY (idtime) REFERENCES nossotime (id)
);
CREATE INDEX fk_nossotime ON idiomatime (idtime);
CREATE TABLE nossotime
(
    id INT(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
    nome VARCHAR(200) NOT NULL,
    funcao VARCHAR(200),
    resumo TEXT,
    curriculo TEXT,
    foto VARCHAR(255),
    status CHAR(1) DEFAULT 'A',
    curriculolotes VARCHAR(255)
);
CREATE TABLE perfil
(
    id INT(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL
);
CREATE TABLE portfolio
(
    bptid INT(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
    bpcid INT(11) NOT NULL,
    bpturl VARCHAR(300) NOT NULL,
    bpttitulo VARCHAR(100),
    bptsubtitulo VARCHAR(200),
    bptlink VARCHAR(400),
    CONSTRAINT fk_categoria_portfolio FOREIGN KEY (bpcid) REFERENCES portfolio_categoria (bpcid)
);
CREATE INDEX fk_categoria_portfolio ON portfolio (bpcid);
CREATE TABLE portfolio_categoria
(
    bpcid INT(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
    bpcdsc VARCHAR(100) NOT NULL,
    bpcvalor VARCHAR(100)
);
CREATE TABLE usuario
(
    id INT(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
    idperfil INT(11) NOT NULL,
    nome VARCHAR(200) NOT NULL,
    email VARCHAR(255) NOT NULL,
    senha VARCHAR(255) NOT NULL,
    foto VARCHAR(255),
    cargo VARCHAR(100),
    descricao VARCHAR(255),
    facebook VARCHAR(255),
    linkedin VARCHAR(255),
    googlep VARCHAR(255),
    twitter VARCHAR(255),
    primeiravez TINYINT(1) DEFAULT '1',
    advogado TINYINT(1) DEFAULT '0',
    CONSTRAINT usuario_ibfk_1 FOREIGN KEY (idperfil) REFERENCES perfil (id)
);
CREATE UNIQUE INDEX email ON usuario (email);
CREATE INDEX email_2 ON usuario (email);
CREATE INDEX idperfil ON usuario (idperfil);