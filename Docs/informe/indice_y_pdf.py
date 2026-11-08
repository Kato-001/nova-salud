# Actualiza el índice (tabla de contenido) del informe y exporta el PDF con LibreOffice.
# Uso (con el Python de LibreOffice, que trae el módulo uno):
#   1) soffice --headless --invisible --norestore "--accept=socket,host=localhost,port=2002;urp;"
#   2) "C:\Program Files\LibreOffice\program\python.exe" indice_y_pdf.py
from pathlib import Path

import uno
from com.sun.star.beans import PropertyValue

DOCS = Path(__file__).resolve().parents[1]
DOCX = DOCS / 'Informe_NovaSalud_Grupo20.docx'
PDF = DOCX.with_suffix('.pdf')


def prop(nombre, valor):
    p = PropertyValue()
    p.Name, p.Value = nombre, valor
    return p


local = uno.getComponentContext()
resolver = local.ServiceManager.createInstanceWithContext('com.sun.star.bridge.UnoUrlResolver', local)
ctx = resolver.resolve('uno:socket,host=localhost,port=2002;urp;StarOffice.ComponentContext')
escritorio = ctx.ServiceManager.createInstanceWithContext('com.sun.star.frame.Desktop', ctx)

doc = escritorio.loadComponentFromURL(uno.systemPathToFileUrl(str(DOCX)), '_blank', 0, (prop('Hidden', True),))
indices = doc.getDocumentIndexes()
for i in range(indices.getCount()):
    indices.getByIndex(i).update()
doc.refresh()
doc.storeToURL(uno.systemPathToFileUrl(str(DOCX)), (prop('FilterName', 'MS Word 2007 XML'),))
doc.storeToURL(uno.systemPathToFileUrl(str(PDF)), (prop('FilterName', 'writer_pdf_Export'),))
paginas = doc.getCurrentController().getPropertyValue('PageCount')
doc.close(True)
print(f'Índice actualizado · {paginas} páginas · {PDF.name}')
