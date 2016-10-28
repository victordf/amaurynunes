use bsv;

create table bsv_carrossel (
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

insert into bsv_carrossel (bcrimg, bcrprincipal, bcrtitulo, bcrsubtitulo) values ('web/images/slider/IMG1.jpg', true, '', '');
insert into bsv_carrossel (bcrimg, bcrtitulo, bcrsubtitulo) values ('web/images/slider/IMG2.jpg', '', '');

create table bsv_portfolio_categoria (
	bpcid int auto_increment not null,
    bpcdsc varchar(100) not null,
    bpcvalor varchar(100),
    primary key(bpcid)
);

create table bsv_portfolio (
	bptid int auto_increment not null,
    bpcid int not null,
    bpturl varchar(300) not null,
    bpttitulo varchar(100),
    bptsubtitulo varchar(200),
    bptlink varchar(400),
    primary key(bptid),
    constraint fk_categoria_portfolio foreign key(bpcid) references bsv_portfolio_categoria(bpcid)
);

insert into bsv_portfolio_categoria (bpcdsc, bpcvalor) values ('HTML', 'html');
insert into bsv_portfolio_categoria (bpcdsc, bpcvalor) values ('Wordpress', 'wordpress');

insert into bsv_portfolio (bpcid, bpturl, bpttitulo, bptsubtitulo, bptlink) values (1, 'web/images/portfolio/1.jpg', 'Teste', 'Teste 1', 'https://www.google.com.br/?gfe_rd=cr&ei=DmfdV9SwBZOF8Qfhpaow&gws_rd=ssl');
insert into bsv_portfolio (bpcid, bpturl, bpttitulo, bptsubtitulo, bptlink) values (2, 'web/images/portfolio/2.jpg', 'Teste', 'Teste 2', 'https://www.google.com.br/?gfe_rd=cr&ei=DmfdV9SwBZOF8Qfhpaow&gws_rd=ssl');
insert into bsv_portfolio (bpcid, bpturl, bpttitulo, bptsubtitulo, bptlink) values (1, 'web/images/portfolio/3.jpg', 'Teste', 'Teste 3', 'https://www.google.com.br/?gfe_rd=cr&ei=DmfdV9SwBZOF8Qfhpaow&gws_rd=ssl');
insert into bsv_portfolio (bpcid, bpturl, bpttitulo, bptsubtitulo, bptlink) values (1, 'web/images/portfolio/4.jpg', 'Teste', 'Teste 4', 'https://www.google.com.br/?gfe_rd=cr&ei=DmfdV9SwBZOF8Qfhpaow&gws_rd=ssl');
insert into bsv_portfolio (bpcid, bpturl, bpttitulo, bptsubtitulo, bptlink) values (1, 'web/images/portfolio/5.jpg', 'Teste', 'Teste 5', 'https://www.google.com.br/?gfe_rd=cr&ei=DmfdV9SwBZOF8Qfhpaow&gws_rd=ssl');
insert into bsv_portfolio (bpcid, bpturl, bpttitulo, bptsubtitulo, bptlink) values (2, 'web/images/portfolio/6.jpg', 'Teste', 'Teste 6', 'https://www.google.com.br/?gfe_rd=cr&ei=DmfdV9SwBZOF8Qfhpaow&gws_rd=ssl');
insert into bsv_portfolio (bpcid, bpturl, bpttitulo, bptsubtitulo, bptlink) values (2, 'web/images/portfolio/7.jpg', 'Teste', 'Teste 7', 'https://www.google.com.br/?gfe_rd=cr&ei=DmfdV9SwBZOF8Qfhpaow&gws_rd=ssl');
insert into bsv_portfolio (bpcid, bpturl, bpttitulo, bptsubtitulo, bptlink) values (2, 'web/images/portfolio/8.jpg', 'Teste', 'Teste 8', 'https://www.google.com.br/?gfe_rd=cr&ei=DmfdV9SwBZOF8Qfhpaow&gws_rd=ssl');
insert into bsv_portfolio (bpcid, bpturl, bpttitulo, bptsubtitulo, bptlink) values (1, 'web/images/portfolio/9.jpg', 'Teste', 'Teste 9', 'https://www.google.com.br/?gfe_rd=cr&ei=DmfdV9SwBZOF8Qfhpaow&gws_rd=ssl');
insert into bsv_portfolio (bpcid, bpturl, bpttitulo, bptsubtitulo, bptlink) values (2, 'web/images/portfolio/10.jpg', 'Teste', 'Teste 10', 'https://www.google.com.br/?gfe_rd=cr&ei=DmfdV9SwBZOF8Qfhpaow&gws_rd=ssl');
insert into bsv_portfolio (bpcid, bpturl, bpttitulo, bptsubtitulo, bptlink) values (1, 'web/images/portfolio/11.jpg', 'Teste', 'Teste 11', 'https://www.google.com.br/?gfe_rd=cr&ei=DmfdV9SwBZOF8Qfhpaow&gws_rd=ssl');
insert into bsv_portfolio (bpcid, bpturl, bpttitulo, bptsubtitulo, bptlink) values (1, 'web/images/portfolio/12.jpg', 'Teste', 'Teste 12', 'https://www.google.com.br/?gfe_rd=cr&ei=DmfdV9SwBZOF8Qfhpaow&gws_rd=ssl');
