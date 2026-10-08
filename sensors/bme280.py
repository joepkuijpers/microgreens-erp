#!/usr/bin/env python3

def read_bme280():
    """
    Tijdelijke testfunctie.
    Zodra de echte BME280 is aangesloten,
    vervangen we deze door de echte uitlezing.
    """
    return {
        "temperature": 22.5,
        "humidity": 58.0,
        "pressure": 1014.0
    }

if __name__ == "__main__":
    print(read_bme280())