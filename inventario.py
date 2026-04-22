import sqlite3
from datetime import datetime
import uuid


class Producto:
    def __init__(self, id, codigo, nombre, cantidad, talla, precio, fecha_creacion):
        self.id = id
        self.codigo = codigo
        self.nombre = nombre
        self.cantidad = cantidad
        self.talla = talla
        self.precio = precio
        self.fecha_creacion = fecha_creacion


class Inventario:
    def __init__(self, db_name="inventario.db"):
        self.db_name = db_name
        self.conectar_db()
        self.crear_tabla()

    def conectar_db(self):
        self.conn = sqlite3.connect(self.db_name)
        self.cursor = self.conn.cursor()

    def crear_tabla(self):
        self.cursor.execute("""
            CREATE TABLE IF NOT EXISTS productos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                codigo TEXT UNIQUE NOT NULL,
                nombre TEXT NOT NULL,
                cantidad INTEGER NOT NULL,
                talla TEXT,
                precio REAL NOT NULL,
                fecha_creacion TEXT NOT NULL
            )
        """)
        self.conn.commit()

    def generar_codigo(self):
        # Generar un código único usando UUID (más robusto que incremental)
        return str(uuid.uuid4())[
            :8
        ].upper()  # Tomar los primeros 8 caracteres y convertir a mayúsculas

    def buscar_por_codigo(self, codigo):
        self.cursor.execute("SELECT * FROM productos WHERE codigo = ?", (codigo,))
        row = self.cursor.fetchone()
        if row:
            return Producto(*row)
        return None

    def crear_producto(self, codigo, nombre, cantidad, talla, precio):
        fecha_creacion = datetime.now().isoformat()
        try:
            self.cursor.execute(
                """
                INSERT INTO productos (codigo, nombre, cantidad, talla, precio, fecha_creacion)
                VALUES (?, ?, ?, ?, ?, ?)
            """,
                (codigo, nombre, cantidad, talla, precio, fecha_creacion),
            )
            self.conn.commit()
            return True
        except sqlite3.IntegrityError:
            return False  # Código duplicado

    def actualizar_producto(self, codigo, cantidad_a_sumar, talla=None, precio=None):
        producto = self.buscar_por_codigo(codigo)
        if not producto:
            return False  # Producto no encontrado

        nueva_cantidad = producto.cantidad + cantidad_a_sumar
        if nueva_cantidad < 0:
            return False  # Cantidad negativa no permitida

        update_fields = ["cantidad = ?"]
        values = [nueva_cantidad]

        if talla is not None:
            update_fields.append("talla = ?")
            values.append(talla)

        if precio is not None:
            values.append(precio)
            update_fields.append("precio = ?")

        values.append(codigo)
        query = f"UPDATE productos SET {', '.join(update_fields)} WHERE codigo = ?"
        self.cursor.execute(query, values)
        self.conn.commit()
        return True

    def validar_datos(self, cantidad, precio):
        try:
            cantidad = int(cantidad)
            precio = float(precio)
            if cantidad < 0 or precio < 0:
                return False, "Cantidad y precio deben ser positivos."
            return True, (cantidad, precio)
        except ValueError:
            return False, "Cantidad debe ser un entero y precio un número válido."

    def cerrar_conexion(self):
        self.conn.close()


# Ejemplo de uso y flujo
def main():
    inventario = Inventario()

    print("Sistema de Inventario - Visión Real")

    while True:
        codigo = input(
            "Ingresa el código del producto (o 'salir' para terminar): "
        ).strip()
        if codigo.lower() == "salir":
            break

        producto = inventario.buscar_por_codigo(codigo)

        if producto:
            print(f"Producto encontrado: {producto.nombre}")
            print(f"Stock actual: {producto.cantidad}")

            cantidad_input = input(
                "Ingresa la cantidad a agregar (puede ser negativa para reducir): "
            ).strip()
            talla = (
                input(
                    "Ingresa la talla (opcional, presiona Enter para mantener actual): "
                ).strip()
                or None
            )
            precio_input = input(
                "Ingresa el nuevo precio (opcional, presiona Enter para mantener actual): "
            ).strip()

            precio = float(precio_input) if precio_input else None

            valido, resultado = inventario.validar_datos(cantidad_input, precio or 0)
            if not valido:
                print(f"Error de validación: {resultado}")
                continue

            cantidad_a_sumar, _ = resultado if precio else (int(cantidad_input), None)

            if inventario.actualizar_producto(codigo, cantidad_a_sumar, talla, precio):
                print("Producto actualizado exitosamente.")
            else:
                print("Error al actualizar el producto.")
        else:
            print("Producto no encontrado. Registrando nuevo producto.")
            nombre = input("Ingresa el nombre del producto: ").strip()
            cantidad_input = input("Ingresa la cantidad inicial: ").strip()
            talla = input("Ingresa la talla: ").strip()
            precio_input = input("Ingresa el precio: ").strip()

            valido, (cantidad, precio) = inventario.validar_datos(
                cantidad_input, precio_input
            )
            if not valido:
                print(f"Error de validación: {resultado}")
                continue

            nuevo_codigo = inventario.generar_codigo()
            if inventario.crear_producto(nuevo_codigo, nombre, cantidad, talla, precio):
                print(f"Producto creado exitosamente con código: {nuevo_codigo}")
            else:
                print("Error al crear el producto (posible código duplicado).")

    inventario.cerrar_conexion()
    print("Sesión terminada.")


if __name__ == "__main__":
    main()
