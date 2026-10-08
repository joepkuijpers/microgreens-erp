#!/usr/bin/env python3

import smbus2
import time

BH1750_ADDRESS = 0x23
POWER_ON = 0x01
RESET = 0x07
CONTINUOUS_HIGH_RES_MODE = 0x10

def read_lux():
    bus = smbus2.SMBus(1)

    bus.write_byte(BH1750_ADDRESS, POWER_ON)
    bus.write_byte(BH1750_ADDRESS, RESET)
    bus.write_byte(BH1750_ADDRESS, CONTINUOUS_HIGH_RES_MODE)

    time.sleep(0.2)

    data = bus.read_i2c_block_data(BH1750_ADDRESS, CONTINUOUS_HIGH_RES_MODE, 2)
    raw = (data[0] << 8) + data[1]
    lux = raw / 1.2

    bus.close()
    return round(lux, 1)

if __name__ == "__main__":
    print(read_lux())