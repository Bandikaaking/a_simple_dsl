# A SIMPLE DSL (ASD)

<img src="./Src/images/logo.png" alt="ASD logo">

Ever wanted to just... **make** an *interpreter*, and just __USE__ it? Well, with ASD, you can do that! Install the **asd** command globaly (for ASD v1.2, you can only install it to Linux systems, And on WSL), and Run **.asd** files, from *everywhere* from you device



## ASD SYNTAX
The syntax of ASD looks like, BASIC becuse:

*("basic" hello world)*
```BASIC
10 PRINT "Hello, World!"
```

*("ASD" hello world)*
```BASIC
PRINT hello
```

# ASD examples!

*(var decleraing + calling)*

```ASD
;!comment
;!
In ASD, we declare varibles like:
SETVAR varname varvalue
you, don't need to put it inside ' "" ' becuse, ASD declares it autmaticly, if it is a , string  a number or boolean
!;
SETVAR y Hello
;!to call them
PRINT =(y)
;!or!
```