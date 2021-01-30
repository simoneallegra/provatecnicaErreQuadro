CREATE DATABASE provatecnicaDB;

USE provatecnicaDB;

CREATE TABLE GifProvider(
    IDprovider VARCHAR (31) UNIQUE,  
    info VARCHAR (255),
    counter_calls INT,
    credits JSON,
    PRIMARY KEY (IDprovider)
);

CREATE TABLE Keyword(
    idkeyword INT NOT NULL AUTO_INCREMENT, 
    keyword VARCHAR (31),
    PRIMARY KEY (idkeyword)
);

CREATE TABLE RelationProvKey(
    idrelation INT NOT NULL AUTO_INCREMENT, 
    IDproviderR VARCHAR (31) REFERENCES  GifProvider(IDprovider),
    idkeywordR INT NOT NULL REFERENCES Keyword(idkeyword),
    counter_search INT,
    PRIMARY KEY (idrelation)
);

