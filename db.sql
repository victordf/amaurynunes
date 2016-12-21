create database amaury;
use amaury;

create table carrossel (
	bcrid int auto_increment,
    bcrimg varchar(300) not null,
    bcrprincipal bool default false,
    bcrtitulo varchar(300),
    bcrsubtitulo varchar(300),
    bcrtembotao bool default false,
    bcrtxtbotao varchar(100),
    bcrfuncbotao varchar(400),
    primary key(bcrid)
);

insert into carrossel (bcrimg, bcrprincipal, bcrtitulo, bcrsubtitulo, bcrtembotao)
values ('web/images/slider/grupo.png', 1, '', '',0);

create table portfolio_categoria (
	bpcid int auto_increment not null,
    bpcdsc varchar(100) not null,
    bpcvalor varchar(100),
    primary key(bpcid)
);

create table portfolio (
	bptid int auto_increment not null,
    bpcid int not null,
    bpturl varchar(300) not null,
    bpttitulo varchar(100),
    bptsubtitulo varchar(200),
    bptlink varchar(400),
    primary key(bptid),
    constraint fk_categoria_portfolio foreign key(bpcid) references portfolio_categoria(bpcid)
);

insert into portfolio_categoria (bpcdsc, bpcvalor) values ('HTML', 'html');
insert into portfolio_categoria (bpcdsc, bpcvalor) values ('Wordpress', 'wordpress');

insert into portfolio (bpcid, bpturl, bpttitulo, bptsubtitulo, bptlink) values (1, 'web/images/portfolio/1.jpg', 'Teste', 'Teste 1', 'https://www.google.com.br/?gfe_rd=cr&ei=DmfdV9SwBZOF8Qfhpaow&gws_rd=ssl');
insert into portfolio (bpcid, bpturl, bpttitulo, bptsubtitulo, bptlink) values (2, 'web/images/portfolio/2.jpg', 'Teste', 'Teste 2', 'https://www.google.com.br/?gfe_rd=cr&ei=DmfdV9SwBZOF8Qfhpaow&gws_rd=ssl');
insert into portfolio (bpcid, bpturl, bpttitulo, bptsubtitulo, bptlink) values (1, 'web/images/portfolio/3.jpg', 'Teste', 'Teste 3', 'https://www.google.com.br/?gfe_rd=cr&ei=DmfdV9SwBZOF8Qfhpaow&gws_rd=ssl');
insert into portfolio (bpcid, bpturl, bpttitulo, bptsubtitulo, bptlink) values (1, 'web/images/portfolio/4.jpg', 'Teste', 'Teste 4', 'https://www.google.com.br/?gfe_rd=cr&ei=DmfdV9SwBZOF8Qfhpaow&gws_rd=ssl');
insert into portfolio (bpcid, bpturl, bpttitulo, bptsubtitulo, bptlink) values (1, 'web/images/portfolio/5.jpg', 'Teste', 'Teste 5', 'https://www.google.com.br/?gfe_rd=cr&ei=DmfdV9SwBZOF8Qfhpaow&gws_rd=ssl');
insert into portfolio (bpcid, bpturl, bpttitulo, bptsubtitulo, bptlink) values (2, 'web/images/portfolio/6.jpg', 'Teste', 'Teste 6', 'https://www.google.com.br/?gfe_rd=cr&ei=DmfdV9SwBZOF8Qfhpaow&gws_rd=ssl');
insert into portfolio (bpcid, bpturl, bpttitulo, bptsubtitulo, bptlink) values (2, 'web/images/portfolio/7.jpg', 'Teste', 'Teste 7', 'https://www.google.com.br/?gfe_rd=cr&ei=DmfdV9SwBZOF8Qfhpaow&gws_rd=ssl');
insert into portfolio (bpcid, bpturl, bpttitulo, bptsubtitulo, bptlink) values (2, 'web/images/portfolio/8.jpg', 'Teste', 'Teste 8', 'https://www.google.com.br/?gfe_rd=cr&ei=DmfdV9SwBZOF8Qfhpaow&gws_rd=ssl');
insert into portfolio (bpcid, bpturl, bpttitulo, bptsubtitulo, bptlink) values (1, 'web/images/portfolio/9.jpg', 'Teste', 'Teste 9', 'https://www.google.com.br/?gfe_rd=cr&ei=DmfdV9SwBZOF8Qfhpaow&gws_rd=ssl');
insert into portfolio (bpcid, bpturl, bpttitulo, bptsubtitulo, bptlink) values (2, 'web/images/portfolio/10.jpg', 'Teste', 'Teste 10', 'https://www.google.com.br/?gfe_rd=cr&ei=DmfdV9SwBZOF8Qfhpaow&gws_rd=ssl');
insert into portfolio (bpcid, bpturl, bpttitulo, bptsubtitulo, bptlink) values (1, 'web/images/portfolio/11.jpg', 'Teste', 'Teste 11', 'https://www.google.com.br/?gfe_rd=cr&ei=DmfdV9SwBZOF8Qfhpaow&gws_rd=ssl');
insert into portfolio (bpcid, bpturl, bpttitulo, bptsubtitulo, bptlink) values (1, 'web/images/portfolio/12.jpg', 'Teste', 'Teste 12', 'https://www.google.com.br/?gfe_rd=cr&ei=DmfdV9SwBZOF8Qfhpaow&gws_rd=ssl');

create table perfil (
    id int auto_increment not null,
    nome varchar(100) not null,
    primary key(id)
);

insert into perfil (nome) values ('Administrador');
insert into perfil (nome) values ('Advogado');


create table usuario (
    id int auto_increment not null,
    idperfil int not null,
    nome varchar(200) not null,
    email varchar(255) not null,
    senha varchar(255) not null,
    foto varchar(255),
    cargo varchar(100),
    descricao varchar(255),
    facebook varchar(255),
    linkedin varchar(255),
    googlep varchar(255),
    twitter varchar(255),
    primeiravez bool default true,
    advogado bool default false,
    primary key(id),
    foreign key(idperfil) references perfil(id),
    unique(email),
    index(email)
);

insert into usuario (
    idperfil,
    nome,
    email,
    senha,
    cargo,
    descricao
) values (
    1,
    'Victor Martins Machado',
    'victormachado90@gmail.com',
    '$2y$04$LDX0xqOKTsL/G2FIadbx0OfwYgT1nzHAuDTMkSieG./wC2FZBMWY2',
    'Desenvolvedor',
    'O maluco que fez essa bagaça'
);

create table artigo (
    id int auto_increment not null,
    titulo varchar(100) not null,
    resumo varchar(250) not null,
    arquivo varchar(100) not null,
    url varchar(255) not null,
    publico bool default false,
    userid int not null,
    datacriacao datetime default now(),
    primary key (id),
    foreign key (userid) references usuario(id)
);

alter table artigo add column link varchar(255);
alter table artigo add column tipoartigo char(1) default 'A';
alter table artigo add column texto text;