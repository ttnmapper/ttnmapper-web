#!/usr/bin/python

def format():
  return "template"

def parse(payload):
  pass

if __name__ == "__main__":
  arv = sys.argv[1:]
  if(len(arv)>0):
    print parse(arv[0])
  else:
    print (format()+" parser")