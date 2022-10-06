 # @AUTHOR  : Denis Horil
 # @LOGIN   : xhoril01
 # @EMAIL   : xhoril01@stud.fit.vutbr.cz
 # @PROJECT : VUT IPP Projekt  - Interpret XML reprezentacie kodu
 #
 #
 # Main script interpret.py
 #

import xml.etree.ElementTree as ET
import sys
import re
from operator import attrgetter

#--------------------------------------------------------- CLASSES ------------------------------------------------------------#

#------------------------------------------------------ Exitus Class ----------------------------------------------------------#

# Class used to print error statement and exit with error code
# 
# Attributes
# ----------
# err_code : int
#       return code number
#
# Methods
# -------
# err_raise()
#       prints error statement and exit with self.err_code
#
class Exitus:
    def __init__(self,err_code):
        self.err_code = err_code

    def err_raise(self):
        if(self.err_code == 10):
            print("ERROR: Missing script parameter or invalid combination of parameters\n", file=sys.stderr)
            
        if(self.err_code == 11):
            print("ERORR: An error occured while opening input files\n", file=sys.stderr)

        if(self.err_code == 12):
            print("ERROR: An error occured while opening output files\n",file=sys.stderr)


        if(self.err_code == 31):
            print("ERROR: Invalid XML format in input file - file is not well-formed\n",file=sys.stderr)

        if(self.err_code == 32):
            print("ERROR: Unexpected XML structure\n",file=sys.stderr)

        if(self.err_code == 52):
            print("ERROR: An error occured by semantic analysis of source code in IPPCode22\n", file=sys.stderr)

        if(self.err_code == 53):
            print("ERROR: Runtime error - invalid operand type\n",file=sys.stderr)

        if(self.err_code == 54):
            print("ERROR: Runtime error - undefined variable\n", file=sys.stderr)

        if(self.err_code == 55):
            print("ERROR: Runtime error - undefined frame\n",file=sys.stderr)

        if(self.err_code == 56):
            print("ERROR: Runtime error - missing value\n", file=sys.stderr)

        if(self.err_code == 57):
            print("ERROR: Runtime error - invalid operand value\n", file=sys.stderr)

        if(self.err_code == 58):
            print("ERROR: Runtime error - invalid operation with string\n", file=sys.stderr)

        if(self.err_code == 99):            
            print("ERROR: Internal error\n",file=sys.stderr)

        sys.exit(self.err_code)
#------------------------------------------------------------------------------------------------------------------------------#

#-------------------------------------------------------- Check Class ---------------------------------------------------------#

# Class used to process <var> type, analyze variables and XML structure and process math instructions
#
# Attributes
# ----------
# None
#
# Methods
# -------
# language(dict)
#       Checks if XML source code has attribute language and if language is valid
#
# string(val)
#       Checks escape sequences in string and replaces them with responding character
#       Returns modified string
#
# symb(arg, GF, LF, TF, isTF)  
#       Checks if <symb> is of type <var> and finds it in frames
#       Returns tuple - type,value
#
# inFrame(list, name, frame, TFdefined=None)
#       Checks for variable in given frame
#       Returns index of found variable
#
# math(args, GF, LFStack, TF, isTF, operation)
#       Compute 2 values according to operation
#       Returns result of computing
#
# compare(args, GF, LFStack, TF, isTF, operator)
#       Compare 2 values according to operator
#       Returns bool value of operation
# 
# logic(args, GF, LFStack, TF, isTF, operation)       
#       Applies konjunction or disjunction on 2 values
#       Returns bool result
#
# writeVar(args, GF, LFStack, TF, isTF, expectedRetVal=None, type=None, value=None)
#       Adds variable to given frame or updates given variable
#
# Parameters of methods
# ---------------------
#       dict : dict         Dictionary of attributes
#       val : str           String to be modified
#       arg : dict          Type and value of argument
#       GF : list           List of global variables
#       LF : list           List of local variables from top of LF Stack
#       LFStack : Stack     Stack of local variables
#       TF : list           List of temporary variables
#       isTF : bool|None    Flag that indicates if TF is defined
#       list : list         List in which function have to look
#       name : str          Name of variable to be found
#       frame : str         Type of frame
#       operation : str     Type of operation to be computed (+,-,*,/)
#       operator : str      Type of opeartor to be compared (<,>,=)
#       oparation : str     Konjunction (add) or Disjunction (or)
#       args : dict         Arguments of instruction
# expectedRetVal : int|None Expected return value
#       type : str          Type of variable
#       value : str|int     Value of variable    
#           
class Check:
    def language(self,dict):
        try:
            language = dict['language']
        except:
            err = Exitus(31)
            err.err_raise()
        
        if(not language == 'IPPcode22'):
            err = Exitus(32)
            err.err_raise()

    def string(self,val):
        if(not val):
            val = ""
            return val

        match = re.search("\\\\\d{3}", val)
        i = 0
        while(not match == None):
            withoutSlash = re.sub(r"\\", "", match.group())
            char = chr(int(withoutSlash))
            
            if(int(withoutSlash) == 92):
                val = re.sub("\\\\\d{3}", "\\\\", val,count=1)
            else:
                val = re.sub("\\\\\d{3}", char, val,count=1)

            match = re.search("\\\\\d{3}", val)
            i+=1 
            
        return val

    def symb(self,arg, GF, LF, TF, isTF):
        for key,value in arg.items():
            if(key == 'string'):
                newVal = self.string(value)
                return key,newVal

            elif(key == 'var'):
                splitted = re.split("@",value)

                if(splitted[0] == 'GF'):
                    index = self.inFrame(GF, splitted[1],splitted[0])
                    if(index == -1):
                        err = Exitus(54)
                        err.err_raise()
                    else:
                        var = GF[index]
                        return var.type,var.value

                if(splitted[0] == 'TF'):
                    index = self.inFrame(TF, splitted[1],splitted[0],isTF)
                    if(index == -1):
                        err = Exitus(54)
                        err.err_raise()
                    else:
                        var = TF[index]
                        return var.type,var.value

                if(splitted[0] == 'LF'):
                    index = self.inFrame(LF, splitted[1], splitted[0])
                    if(index == -1):
                        err = Exitus(54)
                        err.err_raise()
                    else:
                        var = LF[index]
                        return var.type,var.value
            else:
                return key,value

    def inFrame(self, list, name, frame, isTF=None):
        if(list == None):
            err = Exitus(55)
            err.err_raise()

        if(frame == 'GF'):
            for i in range(len(list)):
                var = list[i]
                if(var.name == name):
                    return i

        elif(frame == 'TF'):
            if(isTF):
                for i in range(len(list)):
                    var = list[i]
                    if(var.name == name):
                        return i
            else:
                err = Exitus(55)
                err.err_raise()

        elif(frame == 'LF'):
            for i in range(len(list)):
                var = list[i]
                if(var.name == name):
                    return i

        return -1
    
    def math(self,args, GF, LFStack, TF, isTF, operation):
        for var, toSplit in args[0].items():
            splitted = re.split("@",toSplit)

        type1,val1 = self.symb(args[1],GF,LFStack.stackTop(),TF, isTF)
        type2,val2 = self.symb(args[2],GF,LFStack.stackTop(),TF, isTF)

        if(val1 == None or val2 == None):
            err = Exitus(56)
            err.err_raise()

        if(not type1 == 'int'):
            err = Exitus(53)
            err.err_raise()
        
        if(not type2 == 'int'):
            err = Exitus(53)
            err.err_raise()

        if(operation == '+'):
            value = int(val1) + int(val2)
        elif(operation == '-'):
            value = int(val1) - int(val2)
        elif(operation == '*'):
            value = int(val1) * int(val2)
        elif(operation == '/'):
            if(val2 == '0'):
                err = Exitus(57)
                err.err_raise()
            value = int(val1) / int(val2)
            
        self.writeVar(args, GF, LFStack, TF, isTF, type='int',value=int(value))

    def compare(self,args, GF, LFStack, TF, isTF, operator):
        for var,toSplit in args[0].items():
            splitted = re.split("@", toSplit)
        
        type1,val1 = self.symb(args[1],GF,LFStack.stackTop(),TF, isTF)
        type2,val2 = self.symb(args[2],GF,LFStack.stackTop(),TF, isTF)

        if(val1 == None or val2 == None):
            err = Exitus(56)
            err.err_raise()

        if( not type1 == type2):
            if(type1 == 'nil' or type2 == 'nil'):
                pass
            else:
                err = Exitus(53)
                err.err_raise()

        if(operator == '<'):
            if(type1 == 'nil' or type2 == 'nil'):
                err = Exitus(53)
                err.err_raise()
            if(type1 == 'int'):
                value = str(int(val1) < int(val2)).lower()
            else:
                value = str(val1 < val2).lower()

        elif(operator == '>'):
            if(type1 == 'nil' or type2 == 'nil'):
                err = Exitus(53)
                err.err_raise()
            if(type1 == 'int'):
                value = str(int(val1) > int(val2)).lower()
            else:
                value = str(val1 > val2).lower()

        elif(operator == '='):
            if(type1 == 'nil' or type2 == 'nil'):
                value = str(type1 == type2).lower()

            elif(type1 == 'int'):
                value = str(int(val1) == int(val2)).lower()

            else:
                value = str(val1 == val2).lower()

        self.writeVar(args, GF, LFStack, TF, isTF, type='bool',value=value)
        
    def logic(self,args, GF, LFStack, TF, isTF, operation):
        for var, toSplit in args[0].items():
            splitted = re.split("@", toSplit)

        type1,val1 = self.symb(args[1],GF,LFStack.stackTop(),TF, isTF)
        type2,val2 = self.symb(args[2],GF,LFStack.stackTop(),TF, isTF)

        if(val1 == None or val2 == None):
            err = Exitus(56)
            err.err_raise()

        if(not type1 == 'bool' or not type2 == 'bool'):
            err = Exitus(53)
            err.err_raise()
        if(operation == 'and'):
            if(val1 == val2 and not val1 == 'false'):
                value = 'true'
            else: 
                value = 'false'
                    
        elif(operation == 'or'):
            if(val1 == 'true' or val2 == 'true'):
                value = 'true'
            else:
                value = 'false'   

        self.writeVar(args, GF, LFStack, TF, isTF, type='bool',value=value)
        
    def writeVar(self, args, GF, LFStack, TF, isTF, expectedRetVal=None, type=None, value=None):
        for var, toSplit in args[0].items():
            splitted = re.split("@",toSplit)

        if(splitted[0] == 'GF'):
            retVal = self.inFrame(GF,splitted[1],splitted[0])

            if(retVal == expectedRetVal):
                var = Variable(name=splitted[1], val=value, type=type)
                GF.append(var)

            elif(not retVal == expectedRetVal and not expectedRetVal == None):
                err = Exitus(52)
                err.err_raise()

            elif(retVal == -1 and expectedRetVal == None):
                err = Exitus(54)
                err.err_raise()

            else:
                GF[retVal].value = value
                GF[retVal].type = type
        
        elif(splitted[0] == 'TF'):
            retVal = self.inFrame(TF,splitted[1],splitted[0],isTF)

            if(retVal == expectedRetVal):
                var = Variable(name=splitted[1], val=value, type=type)
                TF.append(var)
            elif(not retVal == expectedRetVal and not expectedRetVal == None):
                err = Exitus(52)
                err.err_raise()
            elif(retVal == -1 and expectedRetVal == None):
                err = Exitus(54)
                err.err_raise()
            else:
                TF[retVal].value = value
                TF[retVal].type = type
            
        elif(splitted[0] == 'LF'):
            retVal = self.inFrame(LFStack.stackTop(),splitted[1],splitted[0])

            if(retVal == expectedRetVal):
                var = Variable(name=splitted[1], val=value, type=type)
                topIndex = LFStack.topIndex
                LFStack.stackList[topIndex].append(var)

            elif(not retVal == expectedRetVal and not expectedRetVal == None):
                err = Exitus(52)
                err.err_raise()

            elif(retVal == -1 and expectedRetVal == None):
                err = Exitus(54)
                err.err_raise()
            else:
                LFStack.stackTop()[retVal].value = value
                LFStack.stackTop()[retVal].type = type

#------------------------------------------------------------------------------------------------------------------------------#

#----------------------------------------------------- Stack Class ------------------------------------------------------------#

# Class used to implement basic functions of ADT Stack
#
# Attributes
# ----------
# topIndex : int
#       Stores index of item on top of stack
# stackList : list
#       List that contains all items of stack        
#
# Methods
# -------
# stackPop()
#       Removes item from stack top
#
# stackPush(data)
#       Adds data to stack top
#
# stackIsEmpty()
#       Returns True if stack is empty, else returns False
#
# stackTop()
#       Returns object from stack top 
#
# stackSize()
#       Returns number of objects in stack 
class Stack:
    def __init__(self):
        self.topIndex = -1
        self.stackList = []
    
    def stackPop(self):
        if(not self.stackIsEmpty()):
            del self.stackList[self.topIndex]
            self.topIndex-= 1
        else:
            return None

    def stackPush(self, data):
        self.topIndex += 1
        self.stackList.insert(self.topIndex, data)

    def stackIsEmpty(self):
        if(self.topIndex == -1):
            return True
        else:
            return False

    def stackTop(self):
        if(not self.stackIsEmpty()):
            return self.stackList[self.topIndex]
        else:
            return None

    def stackSize(self):
        return len(self.stackList)

#------------------------------------------------------------------------------------------------------------------------------#

#----------------------------------------------------- Variable Class ---------------------------------------------------------#

# Class used to implement type Variable
#
# Attributes
# ----------
# name : str
#       Name of variable
# value : Any
#       Value of variable according to type
# type : str
#       Type of variable
#
# Methods
# -------
# None
class Variable:
    def __init__(self, name=None, val=None, type=None):
        self.name = name
        self.value = val
        self.type = type

#------------------------------------------------------------------------------------------------------------------------------#

#------------------------------------------------------ Opcodes Class ---------------------------------------------------------#

# Class used to process every opcode instruction of source code
#
# Attributes
# ----------
# instructions : Element from xml.etree.ElementTree
#       Every instruction from source code
# orderNum : int
#       Number of processed instructions
# actualInstruction : str
#       Name of actual instruction
# inVal : str|None
#       Path to input file given in --input paramter, None if file was not given   
# GF : list
#       Global Frame - list of global variables    
# LFStack : Stack
#       Local Frame - Stack of local variables
# TF : list
#       Temporary Frame - list of temporary variables
# isTF : bool
#       Flag that indicates if temporary frame is active
# dataStack : Stack
#       Stack of values used by POPS and PUSHS
# callStack : Stack
#       Stack of instructions used by RETURN
# labelList : list
#       List of every label in source code
# check : Check
#       Instance of class Check  
#
# Methods
# -------
# letsStartIt()
#       Starts interpretation of source code
# labelListInit()
#       Creates list all labels in source code and their position in code
#
# Methods are sorted into blocks depending on their arguments
# For every instruction exist one method e.g. MOVE will be processed by method op_Move(instruction,order)
# Information about instructions is given in project task
class Opcodes:
    def __init__(self,elem, inVal=None):
        self.instructions = elem
        self.orderNum = 0
        self.actualInstruction = None
        self.inVal = inVal

        self.GF = []
        self.LFStack = Stack()
        self.TF = []
        self.isTF = False

        self.dataStack = Stack()
        self.callStack = Stack()
        self.labelList = []

        self.check = Check()

        self.labelListInit()

    def letsStartIt(self):
        i = 0
        while int(i) < len(self.instructions):
            self.actualInstruction = self.instructions[i].attrib['opcode']

            self.orderNum+= 1

            functionName = funcStart(self.instructions[i])
            i = eval(functionName + "(self.instructions[i],i)")
    
    def labelListInit(self):
        for instruction in self.instructions:
            orderNum = instruction.attrib['order']
            labelName = None
            if(instruction.attrib['opcode'] == 'LABEL'):
                for arg in instruction:
                     labelName = arg.text
                
                for i in range(len(self.labelList)):
                    for name,order in self.labelList[i].items():
                        if(name == labelName):
                            err = Exitus(52)
                            err.err_raise()

                retDict = {labelName : orderNum}
                self.labelList.append(retDict)

#------------- without arguments --------------#
    def op_CreateFrame(self,instruction,order):
        if(not self.isTF):
            self.isTF = True
        else:
            self.TF.clear()

        return int(order+1)

    def op_PushFrame(self,instruction,order):
        if(self.isTF):
            self.LFStack.stackPush(self.TF)
            self.isTF = False
            self.TF = []
        else:
            err = Exitus(55)
            err.err_raise()
        return int(order+1)

    def op_PopFrame(self,instruction,order):
        if(self.LFStack.stackIsEmpty()):
            err = Exitus(55)
            err.err_raise()
        else:
            self.TF = self.LFStack.stackTop()
            self.LFStack.stackPop()
            self.isTF = True
        return int(order+1)

    def op_Return(self,instruction,order):
        if(self.callStack.stackIsEmpty()):
            err = Exitus(56)
            err.err_raise()
        else:
            order = self.callStack.stackTop()
            self.callStack.stackPop()
            return int(order)
        
    def op_Break(self,instruction=None,order=None):
        print(" -------------------- Runtime Info -------------------- \n", file=sys.stderr)
        print(" Number of executed instructions: ", int(self.orderNum)-1, file=sys.stderr)
        print(" Global Frame: ",file=sys.stderr)
        for i in range(len(self.GF)):
                var = self.GF[i]
                print("\t[", var.name,":", var.value,'(',var.type,')', "],",file=sys.stderr)

        print(" Local Frames: ", file=sys.stderr)
        for i in range(self.LFStack.stackSize()):
            frame = self.LFStack.stackList[self.LFStack.stackSize() - (i+1)]
            print(" \t{", file=sys.stderr)

            for j in range(len(frame)):
                var = frame[j]
                print("\t    [",var.name, ":", var.value,'(',var.type,')', "],",file=sys.stderr)

            print(" \t}", file=sys.stderr)

        if(self.isTF):
            print(" Temporary Frame: ", file=sys.stderr)

            for i in range(len(self.TF)):
                var = self.TF[i]
                print("\t[",var.name,":", var.value,'(',var.type,')',"],", file=sys.stderr)
        else:
            print(" Temporary Frame is not defined at the moment", file=sys.stderr)

        print(" Actual instruction: ", self.actualInstruction,file=sys.stderr)

        print(" Data stack:",file=sys.stderr)
        for i in range(self.dataStack.stackSize()):
            var = self.dataStack.stackList[self.dataStack.stackSize() - (i+1)] 
            print(" ",var.type, ":" ,var.value,file=sys.stderr)

        print(" Call stack:",file=sys.stderr)
        for i in range(self.callStack.stackSize()):
            var = self.callStack.stackList[self.callStack.stackSize() - (i+1)] 
            print(" ",var,file=sys.stderr)
        
        print("", file=sys.stderr)
        print(" ------------------------------------------------------ \n", file=sys.stderr)

        if( not order == None):
            return int(order+1)

#------------------ <var> ---------------------#
    def op_Defvar(self,instruction,order):
        args = argReturn(instruction)

        self.check.writeVar(args, self.GF, self.LFStack, self.TF, self.isTF, -1)
        return int(order+1)

    def op_Pops(self,instruction,order):
        args = argReturn(instruction)

        if(not self.dataStack.stackIsEmpty()):
            popVar = self.dataStack.stackTop()
            self.dataStack.stackPop()
            self.check.writeVar(args, self.GF, self.LFStack, self.TF, self.isTF,type=popVar.type, value=popVar.value) 
        else:
            err = Exitus(56)
            err.err_raise()

        return int(order+1)

# ----------------- <label> -------------------#
    def op_Call(self,instruction,order):
        self.callStack.stackPush(int(order+1))
        
        args = argReturn(instruction)
        name = args[0]['label']
        newOrder = self.op_Jump(order=order,callName=name)

        return int(newOrder)

    def op_Label(self,instruction,order):
        return int(order+1)

    def op_Jump(self,instruction=None,order=None, callName=None):
        if(callName == None):
            args = argReturn(instruction)
            name = args[0]['label']
        else:
            name = callName

        for i in range(len(self.labelList)):
            for labelName, jumpOrder in self.labelList[i].items():
                if(labelName == name):
                    return int(jumpOrder)
        
        err = Exitus(52)
        err.err_raise()
                
#------------------ <symb> --------------------#
    def op_Pushs(self,instruction,order):
        args = argReturn(instruction)
        arg = args[0]
        type,value = self.check.symb(arg,self.GF,self.LFStack.stackTop(),self.TF, self.isTF)

        if(value == None):
            err = Exitus(56)
            err.err_raise()

        var = Variable(val=value,type=type)
        self.dataStack.stackPush(var)

        return int(order+1)

    def op_Write(self,instruction,order):
        args = argReturn(instruction)
        arg = args[0]

        type,value = self.check.symb(arg,self.GF,self.LFStack.stackTop(),self.TF, self.isTF)
        if(value == None and type == None):
            err = Exitus(56)
            err.err_raise()
        elif(type == 'nil' or not type):
            print("", end="")    
        else:
            print(value, end="")

        return int(order+1)

    def op_Exit(self,instruction,order):
        args = argReturn(instruction)
        arg = args[0]

        type,value = self.check.symb(arg,self.GF,self.LFStack.stackTop(),self.TF, self.isTF)

        if(value == None):
            err = Exitus(56)
            err.err_raise()

        if(not re.fullmatch(r"[+-]?\d+",value)):
            err = Exitus(53)
            err.err_raise()
        else:
            print("val:",value,file=sys.stderr)
            print("val:",int(value),file=sys.stderr)
            if(int(value) < 0 or int(value) > 49):
                err = Exitus(57)
                err.err_raise()
            else:
                sys.exit(int(value))

        return int(order+1)

    def op_Dprint(self,instruction,order):
        args = argReturn(instruction)
        arg = args[0]
        
        type,value = self.check.symb(arg,self.GF,self.LFStack.stackTop(),self.TF, self.isTF)

        if(value == None):
            err = Exitus(56)
            err.err_raise()

        print(value, file=sys.stderr)
        return int(order+1)

#---------------- <var> <symb> ----------------#
    def op_Move(self,instruction,order):
        args = argReturn(instruction)
        type,value = self.check.symb(args[1], self.GF, self.LFStack.stackTop(), self.TF, self.isTF)
        
        if(value == None):
            err = Exitus(56)
            err.err_raise()

        self.check.writeVar(args, self.GF, self.LFStack, self.TF, self.isTF, type=type,value=value)

        return int(order+1)

    def op_Strlen(self,instruction,order):
        args = argReturn(instruction)   
        type,value = self.check.symb(args[1], self.GF, self.LFStack.stackTop(), self.TF, self.isTF)

        if(value == None):
            err = Exitus(56)
            err.err_raise()

        if(not type == "string"):
            err = Exitus(53)
            err.err_raise()

        self.check.writeVar(args, self.GF, self.LFStack, self.TF, self.isTF, type='int',value=len(value))

        return int(order+1)
            
    def op_Type(self,instruction,order):
        args = argReturn(instruction)
        type,value = self.check.symb(args[1], self.GF, self.LFStack.stackTop(), self.TF, self.isTF)

        if(value == None):
            type = ""
        else:
            value = type
            type = "string"

        self.check.writeVar(args, self.GF, self.LFStack, self.TF, self.isTF, type=type,value=value)

        return int(order+1)

    def op_Int2Char(self,instruction,order):
        args = argReturn(instruction)
        type,value = self.check.symb(args[1], self.GF, self.LFStack.stackTop(), self.TF, self.isTF)

        if(value == None):
            err = Exitus(56)
            err.err_raise()

        if(not type == 'int'):
            err = Exitus(53)
            err.err_raise()


        print(value, value.isdecimal(),file=sys.stderr)
        if(not value.isdecimal()):
            err = Exitus(58)
            err.err_raise()

        try:
            retVal = chr(int(value))
        except:
            err = Exitus(58)
            err.err_raise()

        self.check.writeVar(args, self.GF, self.LFStack, self.TF, self.isTF, type='string',value=retVal)

        return int(order+1)

    def op_Not(self,instruction,order):
        args = argReturn(instruction)
        type,value = self.check.symb(args[1], self.GF, self.LFStack.stackTop(), self.TF, self.isTF)

        if(value == None):
            err = Exitus(56)
            err.err_raise()

        if(not type == 'bool'):
            err = Exitus(53)
            err.err_raise()

        if(not value == 'true' and not value == 'false'):
            err = Exitus(57)
            err.err_raise()

        if(value == 'false'):
            value = 'true'
        else:
            value = 'false'

        self.check.writeVar(args, self.GF, self.LFStack, self.TF, self.isTF, type='bool',value=value)

        return int(order+1)

#---------------- <var> <type> ----------------#
    def op_Read(self,instruction,order):
        args = argReturn(instruction)
        
        type = args[1]['type']
        value = None
                
        if(self.inVal == None):
            value = input()
        elif(not self.inVal):
            value = ""
        else:
            value = self.inVal[0].strip()
            del self.inVal[0]

        if(type == 'bool'):
            if(value.lower() == 'true'):
                value = 'true'
            elif(value == ""):
                value = 'nil'
                type = 'nil'
            else:
                value = 'false'

        elif(type == 'int'):
            if(not re.fullmatch(r"[-+]?\d+", value)):
                type = 'nil'
                value = 'nil'

        self.check.writeVar(args, self.GF, self.LFStack, self.TF, self.isTF, type=type,value=value)
        return int(order+1)

#------------ <var> <symb> <symb> -------------#
    def op_Add(self,instruction,order):
        args = argReturn(instruction)
        self.check.math(args, self.GF, self.LFStack, self.TF, self.isTF, '+')
        return int(order+1)

    def op_Sub(self,instruction,order):
        args = argReturn(instruction)
        self.check.math(args, self.GF, self.LFStack, self.TF, self.isTF, '-')
        return int(order+1)

    def op_Mul(self,instruction,order):
        args = argReturn(instruction)
        self.check.math(args, self.GF, self.LFStack, self.TF, self.isTF, '*')
        return int(order+1)

    def op_Idiv(self,instruction,order):
        args = argReturn(instruction)
        self.check.math(args, self.GF, self.LFStack, self.TF, self.isTF, '/')
        return int(order+1)

    def op_Lt(self,instruction,order):
        args = argReturn(instruction)
        self.check.compare(args, self.GF, self.LFStack, self.TF, self.isTF, "<")
        return int(order+1)

    def op_Gt(self,instruction,order):
        args = argReturn(instruction)
        self.check.compare(args, self.GF, self.LFStack, self.TF, self.isTF, ">")
        return int(order+1)

    def op_Eq(self,instruction,order):
        args = argReturn(instruction)
        self.check.compare(args, self.GF, self.LFStack, self.TF, self.isTF, "=")
        return int(order+1)

    def op_Concat(self,instruction, order):
        args = argReturn(instruction)
        type1, val1 = self.check.symb(args[1], self.GF, self.LFStack.stackTop(),self.TF, self.isTF)
        type2, val2 = self.check.symb(args[2], self.GF, self.LFStack.stackTop(),self.TF, self.isTF)

        if(val1 == None or val2 == None):
            err = Exitus(56)
            err.err_raise()

        if(not type1 == 'string' or not type2 == 'string'):
            err = Exitus(53)
            err.err_raise()

        self.check.writeVar(args, self.GF, self.LFStack, self.TF, self.isTF, type='string',value=val1 + val2)
        return int(order+1)

    def op_And(self,instruction,order):
        args = argReturn(instruction)
        self.check.logic(args, self.GF, self.LFStack, self.TF, self.isTF, 'and')
        return int(order+1)

    def op_Or(self,instruction,order):
        args = argReturn(instruction)
        self.check.logic(args, self.GF, self.LFStack, self.TF, self.isTF, 'or')
        return int(order+1)

    def op_Stri2Int(self,instruction,order):
        args = argReturn(instruction)
        type1,val1 = self.check.symb(args[1], self.GF, self.LFStack.stackTop(), self.TF, self.isTF)
        type2,val2 = self.check.symb(args[2], self.GF, self.LFStack.stackTop(), self.TF, self.isTF) 

        if(val1 == None or val2 == None):
            err = Exitus(56)
            err.err_raise()

        if(not type1 == 'string' or not type2 == 'int'):
            err = Exitus(53)
            err.err_raise()

        if(int(val2) < 0 or int(val2) > len(val1)-1):
            err = Exitus(58)
            err.err_raise()

        try:
            getChar = val1[int(val2)]
            ordVal = ord(getChar)
        except:
            err = Exitus(58)
            err.err_raise()

        self.check.writeVar(args, self.GF, self.LFStack, self.TF, self.isTF, type='int',value=ordVal)
        return int(order+1)

    def op_Getchar(self,instruction,order):
        args = argReturn(instruction)
        type1,val1 = self.check.symb(args[1], self.GF, self.LFStack.stackTop(), self.TF, self.isTF)
        type2,val2 = self.check.symb(args[2], self.GF, self.LFStack.stackTop(), self.TF, self.isTF)

        if(val1 == None or val2 == None):
            err = Exitus(56)
            err.err_raise()

        if(not type1 == 'string' or not type2 == 'int'):
            err = Exitus(53)
            err.err_raise()
        
        if(int(val2) < 0 or int(val2) > len(val1)-1):
            err = Exitus(58)
            err.err_raise()
        else:
            getChar = val1[int(val2)]

        self.check.writeVar(args, self.GF, self.LFStack, self.TF, self.isTF, type='string',value=getChar)
        return int(order+1)

    def op_Setchar(self,instruction,order):
        args = argReturn(instruction)
        for var, toSplit in args[0].items():
            splitted = re.split("@", toSplit)

        type1,val1 = self.check.symb(args[1], self.GF, self.LFStack.stackTop(), self.TF, self.isTF)
        type2,val2 = self.check.symb(args[2], self.GF, self.LFStack.stackTop(), self.TF, self.isTF)

        if(val1 == None or val2 == None):
            err = Exitus(56)
            err.err_raise()


        if(not type1 == 'int' or not type2 == 'string'):
            err = Exitus(53)
            err.err_raise()

        if(splitted[0] == 'GF'):
            retVal = self.check.inFrame(self.GF,splitted[1],splitted[0])
            if(retVal == -1):
                err = Exitus(54)
                err.err_raise()
            else:
                if(not self.GF[retVal].value):
                    err = Exitus(56)
                    err.err_raise()

                if(not self.GF[retVal].type == 'string'):
                    err = Exitus(53)
                    err.err_raise()

                newVal = list(self.GF[retVal].value)

                if(int(val1) < 0 or int(val1) > len(newVal)-1):
                    err = Exitus(58)
                    err.err_raise()

                try: 
                    newVal[int(val1)] = val2[0]
                except:
                    err = Exitus(58)
                    err.err_raise()

                self.GF[retVal].value = "".join(newVal)

        elif(splitted[0] == 'TF'):
                retVal = self.check.inFrame(self.TF,splitted[1],splitted[0], self.isTF)
                if(retVal == -1):
                    err = Exitus(54)
                    err.err_raise()
                else:
                    if(not self.TF[retVal].value):
                        err = Exitus(56)
                        err.err_raise()

                    if(not self.TF[retVal].type == 'string'):
                        err = Exitus(53)
                        err.err_raise()
                
                    newVal = list(self.TF[retVal].value)

                    if(int(val1) < 0 or int(val1) > len(newVal)-1):
                        err = Exitus(58)
                        err.err_raise()

                    try:
                        newVal[val1] = val2[0]
                    except:
                        err = Exitus(58)
                        err.err_raise()

                    self.TF[retVal].value = "".join(newVal)

        elif(splitted[0] == 'LF'):
            retVal = self.check.inFrame(self.LFStack.stackTop(),splitted[1],splitted[0])
            if(retVal == -1):
                err = Exitus(54)
                err.err_raise()
            else:
                if(not self.LFStack.stackTop()[retVal].value):
                    err = Exitus(56)
                    err.err_raise()

                if(not self.LFStack.stackTop()[retVal].type == 'string'):
                    err = Exitus(53)
                    err.err_raise()
                
                newVal = list(self.LFStack.stackTop()[retVal].value)

                if(int(val1) < 0 or int(val1) > len(newVal)-1):
                    err = Exitus(58)
                    err.err_raise()

                try:
                    newVal[val1] = val2[0]
                except:
                    err = Exitus(58)
                    err.err_raise()

                self.LFStack.stackTop()[retVal].value = "".join(newVal)

        return int(order+1)

#----------- <label> <symb> <symb> ------------#
    def op_Jumpifeq(self,instruction,order):
        args = argReturn(instruction)
        labelName = args[0]['label']
        type1,val1 = self.check.symb(args[1], self.GF, self.LFStack.stackTop(), self.TF, self.isTF)
        type2,val2 = self.check.symb(args[2], self.GF, self.LFStack.stackTop(), self.TF, self.isTF)

        if(val1 == None or val2 == None):
            err = Exitus(56)
            err.err_raise()


        is_label = False
        for i in range(len(self.labelList)):
            for name, jumpOrder in self.labelList[i].items():
                if(labelName == name):
                    is_label = True
        if(not is_label):
            err = Exitus(52)
            err.err_raise()                    

        canJump = False
        if(type1 == type2): 
            if(str(val1) == str(val2)):
                canJump = True
        elif(type1 == 'nil' or type2 == 'nil'):
            canJump = False
        else:
            err = Exitus(53)
            err.err_raise()
        
        if(canJump == True):
            print(val1,val2,labelName, file=sys.stderr)
            newOrder = self.op_Jump(order=order, callName=labelName)
            return int(newOrder)
        else:
            return int(order+1)

    def op_Jumpifneq(self,instruction,order):
        args = argReturn(instruction)
        labelName = args[0]['label']
        type1,val1 = self.check.symb(args[1], self.GF, self.LFStack.stackTop(), self.TF, self.isTF)
        type2,val2 = self.check.symb(args[2], self.GF, self.LFStack.stackTop(), self.TF, self.isTF)

        if(val1 == None or val2 == None):
            err = Exitus(56)
            err.err_raise()

        is_label = False
        for i in range(len(self.labelList)):
            for name, jumpOrder in self.labelList[i].items():
                if(labelName == name):
                    is_label = True
        if(not is_label):
            err = Exitus(52)
            err.err_raise()

        canJump = False

        if(type1 == type2):
            if(not str(val1) == str(val2)):
                canJump = True
        elif(type1 == 'nil' or type2 == 'nil'):
            canJump = True
        else:
            err = Exitus(53)
            err.err_raise()
            
        if(canJump):
            newOrder = self.op_Jump(order=order, callName=labelName)
            return int(newOrder)
        else:
            return int(order+1)

#------------------------------------------------------------------------------------------------------------------------------#

#----------------------------------------------------- END OF CLASSES ---------------------------------------------------------#

#-------------------------------------------------------- FUNCTIONS -----------------------------------------------------------#

# Prints help to standard input
# Parameters
# ----------
# None
#
# Returns
# -------
# None
def print_help():
    print("\n--------------------------------- interpret.py ---------------------------------\n\n")
    print("Script interpret.py loads XML representation of source code in IPPcode22 and \n according to command line parameters interprets and generates output\n\n")       
    print("USAGE: python interpret.py [--help][--source=file][--input=file]\n\n")
    print("--help -> prints help to standard output (can't be combined with other parameters)\n")
    print("--source=file -> input file with XML representation\n")
    print("--input=file -> file with inputs for interpretation given source code\n\n")
    print("NOTE: One of argumets --source=file and --inupt=file must be used.\nIf one of them is missing, script will take data from standard input")
    print("--------------------------------------------------------------------------------\n\n")

    sys.exit(0)

# Getting instruction's arguments and their values
# Parameters
# ----------
# instruction : Element from xml.etree.ElementTree
#       Leaf element from root element Tree
#
# Returns
# -------
# list{dict}
#   a list of arguments that have stored their value and type in dictionary
def argReturn(instruction):
    retArg = []
    for args in instruction:
        type = args.attrib['type']
        val = args.text

        retDict = {type : val}
        retArg.append(retDict)

    return retArg

# Python "switch"
# Parameters
# ----------
# instruction : Element from xml.etree.ElementTree
#       Leaf element from root element Tree
#
# Returns
# -------
# str
#   name of function/method to be executed 
def funcStart(instruction):
    name = instruction.attrib['opcode']
    switcher ={
        'CREATEFRAME' : 'self.op_CreateFrame',
        'PUSHFRAME' : 'self.op_PushFrame',
        'POPFRAME' : 'self.op_PopFrame',
        'RETURN' : 'self.op_Return',
        'BREAK' : 'self.op_Break',

        'DEFVAR' : 'self.op_Defvar',
        'POPS' : 'self.op_Pops',

        'CALL' : 'self.op_Call',
        'LABEL': 'self.op_Label',
        'JUMP': 'self.op_Jump',

        'PUSHS': 'self.op_Pushs',
        'WRITE': 'self.op_Write',
        'EXIT': 'self.op_Exit',
        'DPRINT': 'self.op_Dprint',

        'MOVE': 'self.op_Move',
        'STRLEN': 'self.op_Strlen',
        'TYPE': 'self.op_Type',
        'INT2CHAR': 'self.op_Int2Char',
        'NOT': 'self.op_Not',

        'READ': 'self.op_Read',

        'ADD': 'self.op_Add',
        'SUB': 'self.op_Sub',
        'IDIV': 'self.op_Idiv',
        'MUL': 'self.op_Mul',
        'LT': 'self.op_Lt',
        'GT': 'self.op_Gt',
        'EQ': 'self.op_Eq',
        'CONCAT': 'self.op_Concat',
        'AND': 'self.op_And',
        'OR': 'self.op_Or',
        'STRI2INT': 'self.op_Stri2Int',
        'GETCHAR': 'self.op_Getchar',
        'SETCHAR': 'self.op_Setchar',

        'JUMPIFEQ': 'self.op_Jumpifeq',
        'JUMPIFNEQ': 'self.op_Jumpifneq'  
    }
    
    return switcher.get(name.upper())

#------------------------------------------------------ END OF FUNCTIONS -----------------------------------------------------#

#----------------------------------------------------------- MAIN ------------------------------------------------------------#
def main():
    inputFile = None
    sourceFile = None
    inputVal = None

    # Argument check
    for i in range(len(sys.argv)):
        if(sys.argv[i] == '--help'):
            if(len(sys.argv) > 2):
                err = Exitus(10)
                err.err_raise()
            else:
                print_help()

        elif(re.search("^--source=",sys.argv[i])):
            sourceFile = re.sub("^--source=","",sys.argv[i])
        elif(re.search("^--input=.",sys.argv[i])):
            inputFile = re.sub("^--input=","",sys.argv[i])

        elif(sys.argv[i] == __file__ ):
            pass

        else:
            err = Exitus(10)
            err.err_raise()

    # Validating cmd line arguments
    if(inputFile == None and sourceFile == None):
        err = Exitus(10)
        err.err_raise()

    # Script will expect source code from STDIN   
    if(not sourceFile):
        sourceFile = input()
        tree = ET.fromstring(sourceFile)
    else:
        tree = ET.parse(sourceFile)
    
    # Script will take values from given file
    if(inputFile):
        f = open(inputFile, 'r')
        inputVal = f.readlines()
        f.close()

    # Loading XML file    
    root = tree.getroot()

    # Language check
    check = Check()
    check.language(root.attrib)

    # Sorting instructions by arguments
    for node in root.findall("*"): 
        node[:] = sorted(node, key=attrgetter("tag"))

    # Sorting instructions by order
    instructions = list(root)
    for instruction in instructions:
        if(not instruction.attrib['order'].isdecimal()):
            err = Exitus(32)
            err.err_raise()
        else:
            instruction.attrib['order'] = int(instruction.attrib['order'])

    # Changing order - starting from 1
    instructions[:] = sorted(instructions, key=lambda i: i.get('order'))
    for i in range(len(instructions)):
        instructions[i].set('order', i+1)

    # Start of interpretation
    opcode = Opcodes(instructions,inputVal)
    opcode.letsStartIt()
    sys.exit(0)
#-------------------------------------------------------- END OF MAIN --------------------------------------------------------#
main()